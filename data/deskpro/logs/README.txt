This directory contains all application logs.

More information about logging can be found here:
https://support.deskpro.com/guides/topic/1844

Helpful hints:

* You can set `LOGS_OUTPUT_FORMAT=json` in `config/config.env` to have logs output as JSON.

* You can completely remove this directory if you don't want logs written to disk. Logs will instead be written to the container standard output, which you can then handle via normal Docker logging facilities (see https://docs.docker.com/config/containers/logging/).