ARG VARIANT=8-bullseye
FROM mcr.microsoft.com/vscode/devcontainers/php:${VARIANT}

# Node.jsも使っちゃう場合
ARG NODE_VERSION="16"
RUN if [ "${NODE_VERSION}" != "none" ]; then su vscode -c "umask 0002 && . /usr/local/share/nvm/nvm.sh && nvm install ${NODE_VERSION} 2>&1"; fi

# Composerインストール
COPY --from=composer /usr/bin/composer /usr/bin/composer

# 何かしら追加パッケージをインストールする
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash
RUN apt install -y symfony-cli \
                sqlite3

# PHPのエクステンションを追加
RUN docker-php-ext-enable xdebug
RUN docker-php-ext-install pdo_mysql