To enable HTTPS:

* In this directory, create two new sub-directories: `certs/` and `private/`
* Copy your SSL certificate (pem format) to: `certs/deskpro-https.crt`
* Copy your (unencrypted) key to: `private/deskpro-https.key`

Then restart the Deskpro web container:

```
docker-compose restart deskpro_web
```
