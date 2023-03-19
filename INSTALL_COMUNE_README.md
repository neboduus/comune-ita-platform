# How to install a new comune

Using this repo is possible to install an entire Website for italian
municipalities (Comuni). To do so follow the following steps:

1. Clone the git repository git@gitlab.com:neboduus/core.git into a new folder,
  for example if you're preparing Comune `comune-1`:

```bash
git clone git@gitlab.com:neboduus/core.git comune-1
```

2. Create the configuration file for the municipality that you want to deploy:

```bash
cd comune-1
cp comune_platform/env/public/template comune_platform/env/public/comune-1
cp comune_platform/env/secrets/template comune_platform/env/secrets/comune-1
```

3. Edit the files you just created with the configurations you want.

4. Create the nginx-conf of this comune

```bash
mkdir nginx-conf/comune-1
cp nginx-conf/nginx.template.conf nginx-conf/comune-1/nginx.conf
```

5. Adjust `nginx-conf/comune-1/nginx.conf` by changing `localhost` from
   `server_name localhost;` line with your server name. It could be either an
   IP (e.g. 34.35.35.123) or a domain name (e.g. comunealdeno.com)

6. Build the core:

```bash
make build-core-comune ENV=comune-1
```

7. Start the deployment:

```
make deploy-comune ENV=comune-1
```

> If the Make commands do not work, it may be that the Compose Version does not
  allow the Make commands to perform correctly. Then just deploy manually,
  with following code:

```shell
export ENV=comune-1
export PUBLIC_ENV_VARS=$(cat comune_platform/env/public/$ENV)
export SECRET_ENV_VARS=$(cat comune_platform/env/secrets/$ENV)
env $PUBLIC_ENV_VARS $SECRET_ENV_VARS ENV=comune-1 docker compose up -d
```

8. Access the deployment at following URLs:

   - Website: http://localhost:NGINX_PORT (replace NGINX_PORT with the port
     you configured in step 3).
   - Citizen's room: http://stanzadelcittadino.localtest.me/

> Note: Please make sure to enter the commands as shown, replacing comune-1
  with the name of your municipality, and NGINX_PORT with the
  port you configured in step 3.
