<?php
#######################################################################
# This helper writes basic config with random secrets for you.
#
# You should not call this script directly from your own computer.
# Instead, call it via docker-compose (from the directory above this one):
#
#     docker-compose run -it --rm init_config_files
#
# After this has been run once, this file may be safely deleted.
#######################################################################

final class init_config_files
{
	private readonly string $configPath;

	public function __construct(
		private readonly string $projectPath
	) {
		$this->configPath = "$projectPath/config";
	}

	public function run(): int
	{
		$didSkipAny = false;

		if (!file_exists("{$this->configPath}/MYSQL_ROOT_PASSWORD.txt")) {
			file_put_contents("{$this->configPath}/MYSQL_ROOT_PASSWORD.txt", $this->generatePassword());
			echo "OK\n";
		} else {
			echo "SKIPPED - file already exists\n";
			$didSkipAny = true;
		}

		echo "Generating secrets DESKPRO_DB_PASS.txt ... ";
		if (!file_exists("{$this->configPath}/DESKPRO_DB_PASS.txt")) {
			file_put_contents("{$this->configPath}/DESKPRO_DB_PASS.txt", $this->generatePassword());
			echo "OK\n";
		} else {
			echo "SKIPPED - file already exists\n";
			$didSkipAny = true;
		}

		if (!file_exists("{$this->configPath}/DESKPRO_APP_KEY.txt")) {
			file_put_contents("{$this->configPath}/DESKPRO_APP_KEY.txt", $this->generateAppSecret());
			echo "OK\n";
		} else {
			echo "SKIPPED - file already exists\n";
			$didSkipAny = true;
		}

		$dockerComposeFile = "$projectPath/docker-compose.yml";
		if (file_exists($dockerComposeFile)) {
			echo "Removing init_config_files from docker-compose.yml ... "
			$content = file_get_contents($dockerComposeFile);
			$content = preg_replace('/#deskpro:init:begin.*?#deskpro:init:end/s', '', $content);
			echo "OK\n";

			echo "Detecting latest version ... \n";
			$latest = $this->getDeskproImage();
			$setLine = 'DESKPRO_IMAGE="'.$latest.'"';
			$newContent = preg_replace('/^DESKPRO_IMAGE=.*$/m', $setLine, $content);
			echo "OK\n";
		}

		echo "\nAll done.\n";

		return 0;
	}

	private function generateAppSecret(): string
	{
		return base64_encode(random_bytes(32));
	}

	private function generatePassword(int $len = 48): string
	{
		return bin2hex(openssl_random_pseudo_bytes($len));
	}

	private function getDeskproImage(): string
	{
		$ctx = stream_context_create(['http'=>
		    [
				'timeout' => 10,
				'follow_location' => 1,
				'user_agent' => 'deskpro/docker-compose-example/1.0',
		    ]
		]);

		$versionManifest = @file_get_contents('https://get.deskpro.com/manifest.json', false, $ctx) ?: [];

		$versionManifest = @json_decode($versionManifest) ?: null;
		$newest = array_reduce($versionManifest->releases ?? [], function (?object $newest, object $v) {
			$m = null;
			if (!preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $v->version, $m)) {
				// exclude pre-release tagged
				return $newest;
			}

			if (!$newest) {
				return $v;
			}

			return version_compare($v->version, $newest->version, '>') ? $v : $newest;
		}, null);
		if (!empty($newest->docker_tag)) {
			return "docker.io/deskpro/deskpro-product:{$newest->docker_tag}-onprem";
		}
		
		return "docker.io/deskpro/deskpro-product:2023.43.0-onprem";
	}
}

$main = new init_config_files($_SERVER['argv'][1]);
exit($main->run());
