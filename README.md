This project is an example docker-compose project that runs Deskpro and all required services.

The Deskpro image repository can be found at:
https://hub.docker.com/r/deskpro/deskpro-product/tags?page=1&name=onprem

Full documentation on how the Deskpro image operates can be found here:
https://support.deskpro.com/guides/topic/1841

---

- [1.0. Getting started](#10-getting-started)
  - [1.1. Run Init](#11-run-init)
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

To use this example docker-compose project, either clone this repos:

```
git clone https://github.com/deskpro/docker-compose-example.git
```

Or download the source and extract it:

```
curl -L https://github.com/deskpro/docker-compose-example/archive/refs/heads/main.zip -o deskpro-compose.zip
unzip deskpro-compose.zip
```

## 1.2. Run Init

The first thing you need to do is run the 'init' helper that will generate basic config for you:

```
docker-compose run -it --rm init
```

Optional: After running this once, you can edit docker-compose.yml to remove the "init" task from the top of the file.

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

You now need to initialize the database by running the installer.

First, open a bash shell:

```
docker-compose run -it --rm deskpro_bash
```

Once you are in, you can run the installer:

```
./bin/install --url 'http://127.0.0.1/' --adminEmail 'your@email.com' --adminPassword 'password'
```

Note: If you already know you will be using other ports, HTTPS, or a domain name, you can set the URL accordingly. The next section of this README will go over what other configuration you need to make it work.

## 1.5. Start Deskpro

Finally, you can go ahead and start Deskpro itself:

```
docker-compose up -d
```

When services have started, you can open http://127.0.0.1/app in your browser.

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

Edit the .env file (that exists in this same directory) and change the ports in the two variables at the top of the file.

For example, if you wish to use http://127.0.0.1:8080/, then you would change HTTP_USER_SET_HTTP_PORT=8080

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
docker image pull deskpro/deskpro-product:2023.33.1-onprem
```

This step just pre-downloads the image so the following steps finish faster This will ensure the image actually exists, and also reduces downtime for your users.

**Step 2: Change the container name in config**

Edit .env and change the DESKPRO_IMAGE variable to the new image.

For example:

```
DESKPRO_IMAGE=deskpro/deskpro-product:2023.33.1-onprem
```

**Step 3: Stop Deskpro services**

Stop Deskpro by running:

```
docker-compose down deskpro_web deskpro_tasks
```

**Step 4: Optionally make a database backup**

Before you run database migrations, you should make a database backup in case you need to revert to the previous version for whatever reason.

The simplest way to do this is by exporting a full dump:

```
docker-compose run -it --rm mysql_make_dump
```

**Step 5: Run migrations**

When Deskpro has shut down, and you have made a backup, you can then run migrations to install any required database updates.

```
docker-compose run -it --rm deskpro_run_migrations
```

**Step 6: Start Deskpro services again**

Finally, you can bring Deskpro back up:

```
docker-compose up -d
```


