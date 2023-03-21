#VERSION := $(shell cat version.txt)
ENV ?=
PUBLIC_ENV_VARS := $(shell cat comune_platform/env/public/${ENV})
SECRET_ENV_VARS := $(shell cat comune_platform/env/secrets/${ENV})
OPTIONS ?=

require-defined-env:
	$(if $(ENV),,$(error Must set ENV. E.g. `make some-command ENV=local`))

show-env: require-defined-env
	cat comune_platform/env/public/$(ENV) && cat comune_platform/env/secrets/$(ENV)

require-i-am-sure:
	$(if $(I_AM_SURE),,$(error Must set I_AM_SURE=yes. E.g. `make delete-all-data I_AM_SURE=yes`))

docker-compose-cli-deploy-comune:
	env $(PUBLIC_ENV_VARS) $(SECRET_ENV_VARS) \
	    docker-compose \
        -f docker-compose.comune.yml \
         up \
        -d postgres
	sleep 20
	env $(PUBLIC_ENV_VARS) $(SECRET_ENV_VARS) \
	    docker-compose \
	        -f docker-compose.comune.yml \
	        up \
	        $(OPTIONS)

docker-compose-plugin-deploy-comune:
	env $(PUBLIC_ENV_VARS) $(SECRET_ENV_VARS) \
	    docker compose \
        -f docker-compose.comune.yml \
        up \
        -d postgres
	sleep 20
	env $(PUBLIC_ENV_VARS) $(SECRET_ENV_VARS) \
	    docker compose \
	        -f docker-compose.comune.yml \
	        up \
	        $(OPTIONS)

deploy-comune: require-defined-env
	${MAKE} docker-compose-plugin-deploy-comune || ${MAKE} docker-compose-cli-deploy-comune

docker-compose-cli-build-core:
	env $(PUBLIC_ENV_VARS) $(SECRET_ENV_VARS) \
	    docker-compose \
	        -f docker-compose.comune.yml \
	        build \
	        $(OPTIONS)

docker-compose-plugin-build-core:
	env $(PUBLIC_ENV_VARS) $(SECRET_ENV_VARS) \
	    docker compose \
	        -f docker-compose.comune.yml \
	        build \
	        $(OPTIONS)

build-core-comune: require-defined-env
	${MAKE} docker-compose-plugin-build-core || ${MAKE} docker-compose-cli-build-core

stop-deployment-comune: require-defined-env
	env $(PUBLIC_ENV_VARS) $(SECRET_ENV_VARS) \
	    docker-compose \
	        -f docker-compose.comune.yml \
	        down \
	        $(OPTIONS)

# Do not use yet
# delete-all-data: require-i-am-sure
# 	@printf "WARNING: This command will delete all data\n"
# 	cp -r data/mysql data/mysql_tmp
# 	rm -rf data/mysql/*
# 	mv data/mysql_tmp/.gitkeep data/mysql
# 	rm -rf data/mysql_tmp
# 	cp -r data/wp data/wp_tmp
# 	rm -rf data/wp/*
# 	mv data/wp_tmp/.gitkeep data/wp
# 	rm -rf data/wp_tmp
# else
# 	$(CC) -o foo $(objects) $(normal_libs)
# endif