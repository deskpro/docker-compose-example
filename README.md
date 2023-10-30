This project is an example docker-compose project that runs Deskpro and all required services.

The Deskpro image repository can be found at:
https://hub.docker.com/r/deskpro/deskpro-product/tags?page=1&name=onprem

Full documentation on how the Deskpro image operates can be found here:
https://support.deskpro.com/guides/topic/1841

---

- [1.0. Getting started](#10-getting-started)
  - [1.1. Basic Configuration](#11-basic-configuration)
  - [1.2. Start MySQL and Elastic](#12-start-mysql-and-elastic)
  - [1.3. Run the installer](#13-run-the-installer)
  - [1.4. Start Deskpro](#14-start-deskpro)
- [2.0. Using a different URL](#20-using-a-different-url)
  - [2.1. Enabling HTTPS](#21-enabling-https)
  - [2.2. Using different ports](#22-using-different-ports)
  - [2.3. Using a domain name](#23-using-a-domain-name)
  - [2.4. Changing the helpdesk URL](#24-changing-the-helpdesk-url)
- [3.0. Updating Deskpro](#30-updating-deskpro)

---

# 1.0. Getting started

## 1.1 Download the example project

Download the latest version and extract it:

```
curl -L https://github.com/deskpro/docker-compose-example/archive/refs/heads/main.zip -o deskpro-compose.zip
unzip deskpro-compose.zip
```

Or you can just clone the repos:

```
git clone https://github.com/deskpro/docker-compose-example.git
```

## 1.2. Basic Configuration

### Automatically

The simplest way to get started is to use the provided helper script to automatically generate the necessary secrets for you.

You can run the helper via `docker-compose` like this:

```
docker-compose run -it --rm init_config_files
```

### Manually

**1. Use the latest Deskpro image**

Find the latest release from:
https://hub.docker.com/r/deskpro/deskpro-product/tags?page=1&name=onprem

Then edit the `.env` file and change the image to use the latest. For example:

```
# Replace the XXXX.XX.X with the latest published version
DESKPRO_IMAGE=docker.io/deskpro/deskpro-product:XXXX.XX.X-onprem
```

**2. Secrets / Passwords**

You need to modify these three files to set secrets:

* `config/MYSQL_ROOT_PASSWORD.txt` - MySQL root password
  * Example: ``
* `config/DESKPRO_DB_PASS.txt` - MySQL user password used by the Deskpro database user
* `config/DESKPRO_APP_KEY.txt` - A random 32-character string used for cryptographic functions

## 1.3. Start Services

Now you can bring up MySQL and Elastic:

```
docker-compose --profile services up -d
```

It's a good idea to a wait a few seconds and then ensure MySQL is running as expected. You can test the connection like this:

```
docker-compose run -it --rm deskpro_bash exec mysql-primary -e "SHOW DATABASES;"
```

When this succeeds, you can move on.

## 1.4. Run the installer

You now need to run the installer that will install the database and set up default options.

First, start a command line container:

```
docker-compose run -it --rm deskpro_cli
```

Once you are in, you can run the installer:

```
./bin/install --url 'http://127.0.0.1/' --adminEmail 'your@email.com' --adminPassword 'password'
```

Note: If you already know you will be using other ports, HTTPS, or a domain name, you can set the URL accordingly. The next section of this README will go over what other configuration you need to make it work.

## 1.5. Start Deskpro

Finally, you can go ahead and start Deskpro itself by bringing up the other containers:

```
docker-compose up -d
```

When services have started, you can open http://127.0.0.1/app in your browser and log in using the credentials you provided when you ran the installer.

---

# 2.0. Using a different URL

## 2.1. Enabling HTTPS

To enable HTTPS, you need a valid certificate.

* Create two new directories: `data/deskpro/config/certs` and `data/deskpro/config/private`
* Copy your SSL certificate (pem format) to: `data/deskpro/config/certs/deskpro-https.crt`
* Copy your (unencrypted) key to: `data/deskpro/config/private/deskpro-https.key`

Then restart the Deskpro web container:

```
docker-compose restart deskpro_web
```

## 2.2. Using different ports

If you want to run the web server on different ports, you need to configure port mapping.

Edit the `.env` file and change the ports in the two variables at the top of the file.

For example, if you wish to use http://127.0.0.1:8080/, then you would change `HTTP_USER_SET_HTTP_PORT=8080`

After you make these changes, restart the Deskpro web container:

```
docker-compose restart deskpro_web
```

## 2.3. Using a domain name

You don't need to do anything special to start using a domain name. You just need to make sure your server is open to the internet and that DNS resolves to the server.

## 2.4. Changing the helpdesk URL

Any time you want to change your helpdesk URL (e.g. to enable HTTPS, change ports, or start using a domain name), then you should also change the URL setting.

Just navigate to http://your-url/app, then go to Admin > Branding to change the URL.

---

# 3.0. Updating Deskpro

**Step 1: Pull the latest image**

First, find the version you want to update to:
https://hub.docker.com/r/deskpro/deskpro-product/tags?page=1&name=onprem

Then you can pull that image to pre-cache it:

```
docker image pull deskpro/deskpro-product:2023.43.0-onprem
```

This step just pre-downloads the image so the following steps finish faster. This will ensure the image actually exists, and also reduces downtime for your users.

**Step 2: Change the image in config**

Edit the `.env` file and change the DESKPRO_IMAGE variable to the new image.

For example:

```
DESKPRO_IMAGE=deskpro/deskpro-product:2023.43.0-onprem
```

**Step 3: Stop Deskpro services**

Stop the currently-running Deskpro containers by running:

```
docker-compose down deskpro_web deskpro_tasks
```

**Step 4: Optionally make a database backup**

Before you run database migrations, you should make a database backup in case you need to revert to the previous version for whatever reason.

The simplest way to do this is by exporting a full dump. There is a helper service defined in `docker-compose.yml` that will run a `mysqldump` command for you:

```
docker-compose run -it --rm mysql_make_dump
```

**Step 5: Restart Deskpro**

When Deskpro has shut down and you have made a backup, you can restart the Deskpro containers using the new image:

```
docker-compose up -d
```

If migrations need to run, then they will run automatically (via the `deskpro_tasks` container). Note that the web interface won't be usable until migrations finish running.

**Troubleshooting**

If you've upgraded and the Deskpro web interface stays disabled, it usually means that migrations are still running. You just need to wait a few minutes for migrations to finish running in the background.

You can review the docker logs for the container if you want to get a clearer picture of what is going on:

```
docker-compose logs -f deskpro_tasks
```

From the logs, you will be able to see what is currently running (i.e. a specific migration step), or any errors or problems that might have happened during the upgrade procedure.

## 3.1 Manually running migrations

If you prefer to manually run migrations instead of having them run automatically, there are two steps:

**1. Disable auto-migrations in config**

In your `docker-compose.yml` file, _remove_ the line `AUTO_RUN_MIGRATIONS=true` that exists under the `deskpro_tasks` service.

**2. Manually run the migrations command**

When you update the `DESKPRO_IMAGE` version as described above, you will then also need to manually run the migrations command.

First, start a command line container:

```
docker-compose run -it --rm deskpro_cli
```

Once you are in, you can run migrations like this:

```
./tools/migrations/artisan migrations:exec -vvv --run
```
