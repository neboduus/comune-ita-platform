# Environment DEMO

https://demosdc.opencontent.it/

## Aggiornamento certificato

    cd /srv/sdc
    docker-compose stop traefik
    docker-compose stop apache 
    sudo certbot certonly -d demosdc.opencontent.it
    # edit docker-compose.yml file and update path of letsencrypt certs
    docker-compose up -d
 
