FROM jenkins/jenkins:lts
USER root
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip curl ca-certificates \
    php-cli php-mbstring php-xml php-curl php-zip php-bcmath php-intl php-gd \
    php-sqlite3 php-mysql php-xdebug nodejs npm \
 && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
USER jenkins