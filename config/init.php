<?php
#######################################################################
# This script will init the basic config env files and generate
# random secrets for you.
#
# You should not call this script directly from your own computer.
# Instead, call it via docker-compose (from the directory above this one):
#
#     docker-compose run -it --rm init
#
# After this has been run once, this file may be safely deleted.
#######################################################################

ini_set('default_socket_timeout', 10);

final class init_config
{
	private readonly string $configPath;

	public function __construct(
		private readonly string $projectPath
	) {
		$this->configPath = "$projectPath/config";
	}

	public function run(): int
	{
		if (file_exists("{$this->configPath}/config.env")) {
			echo "!!! Error: config.env already exists\n";
			echo "If you want to run init again, delete everything but init.php in the config/ directory\n";
			return 1;
		}

		echo "Generating secrets ... ";
		file_put_contents("{$this->configPath}/DESKPRO_DB_PASS.txt", $this->generatePassword());
		file_put_contents("{$this->configPath}/MYSQL_ROOT_PASSWORD.txt", $this->generatePassword());
		file_put_contents("{$this->configPath}/DESKPRO_APP_KEY.txt", $this->generateAppSecret());
		echo "OK\n";

		echo "Writing env files... ";
		file_put_contents("{$this->configPath}/config.env", $this->configEnv());
		file_put_contents("{$this->configPath}/mysql.env", $this->mysqlEnv());
		file_put_contents("{$this->configPath}/elastic.env", $this->elasticEnv());
		file_put_contents("{$this->configPath}/elastic.env", $this->elasticEnv());
		file_put_contents("{$this->projectPath}/.env", $this->rootEnv());
		echo "OK\n";

		$httpPort = getenv('HTTP_USER_SET_HTTP_PORT') ?: '80';
		if ($httpPort && $httpPort != 80) {
			$localUrl = "http://127.0.0.1:$httpPort/";
		} else {
			$localUrl = "http://127.0.0.1/";
		}
		
		echo <<<EOT
Next steps:

===== 1. Start MySQL and Elastic =====

Bring up MySQL and Elastic services like so:

  docker-compose --profile services up -d


===== 2. Run the installer =====

Then run the Deskpro installer to initialize the database:

  # Open the bash shell first:
  docker-compose run -it --rm deskpro_bash

  # Then run the installer:
  ./bin/install --url '$localUrl' --adminEmail 'your@email.com' --adminPassword 'password'

Note: Read README.txt for more information about using other ports, using a domain name, and enabling HTTPS.


===== 3. Start Deskpro services =====

Finally, start the Deskpro app services:

  docker-compose --profile deskpro up -d

Once that is done, you can open http://127.0.0.1/app in your browser.

EOT;

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
		return "docker.io/deskpro/deskpro-product:2023.34.0-onprem";
		$versionManifest = @file_get_contents('https://get.deskpro.com/manifest.json');
		$deskproImage = null;

		if ($versionManifest) {
			$versionManifest = @json_encode($versionManifest) ?: null;
			$newest = array_reduce($versionManifest['releases'] ?? [], function (array $newest, array $v) {
				$m = null;
				if (!preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $v['version'], $m)) {
					// exclude pre-release tagged
					return $newest;
				}

				if (!$newest) {
					return $v;
				}

				return version_compare($v['version'], $newest['version'], '>');
			}, null);
			if (!empty($newest['docker_tag'])) {
				return "docker.io/deskpro/deskpro-product:{$newest['docker_tag']}-onprem";
			}
		}
		
		return "docker.io/deskpro/deskpro-product:2023.33.1-onprem";
	}

	private function configEnv(): string
	{
		return <<<EOT
DESKPRO_DB_HOST="mysql"
DESKPRO_DB_PORT="3306"
DESKPRO_DB_USER="deskpro"
DESKPRO_DB_PASS_FILE="/run/secrets/DESKPRO_DB_PASS"
DESKPRO_DB_NAME="deskpro"
DESKPRO_ES_URL="http://elastic:9200"
DESKPRO_ES_INDEX_NAME="deskpro"
DESKPRO_APP_KEY_FILE="/run/secrets/DESKPRO_APP_KEY"
EOT;
	}

	private function mysqlEnv(): string
	{
		return <<<EOT
MYSQL_ROOT_PASSWORD_FILE="/run/secrets/MYSQL_ROOT_PASSWORD"
MYSQL_DATABASE=deskpro
MYSQL_USER=deskpro
MYSQL_PASSWORD_FILE="/run/secrets/DESKPRO_DB_PASS"
EOT;
	}

	private function elasticEnv(): string
	{
		return <<<EOT
xpack.security.enabled=false
discovery.type=single-node
ES_JAVA_OPTS=-Xms2g -Xmx2g
EOT;
	}

	private function rootEnv(): string
	{
		return <<<EOT
# The version of Deskpro to use
DESKPRO_IMAGE="{$this->getDeskproImage()}"

# The port on the host mapped to port 80 and 443 in the container
HTTP_USER_SET_HTTP_PORT=80
HTTP_USER_SET_HTTPS_PORT=443

COMPOSE_PROFILES="services,deskpro"
EOT;
	}
}

$main = new init_config($_SERVER['argv'][1]);
exit($main->run());
