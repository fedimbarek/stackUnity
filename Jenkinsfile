pipeline {
    agent any

    options {
        timestamps()
        disableConcurrentBuilds()
        buildDiscarder(logRotator(numToKeepStr: '15'))
        skipDefaultCheckout(true)
        timeout(time: 45, unit: 'MINUTES')
    }

    parameters {
        booleanParam(name: 'DEPLOY_RENDER', defaultValue: true,
            description: 'Declencher Render apres succes, seulement sur main')
    }

    environment {
        APP_ENV = 'testing'
        APP_DEBUG = 'false'
        DB_CONNECTION = 'mysql'
        DB_HOST = 'mysql'
        DB_PORT = '3306'
        DB_DATABASE = 'heatalert_test'
        DB_USERNAME = 'heatalert_ci'
        DB_PASSWORD = 'ci-local-only-change-me'
        CACHE_STORE = 'array'
        SESSION_DRIVER = 'array'
        QUEUE_CONNECTION = 'sync'
        MAIL_MAILER = 'array'
        XDEBUG_MODE = 'coverage'
        COMPOSER_NO_INTERACTION = '1'
        DOCKER_IMAGE = 'meddhia1rom/stackunity'
    }

    stages {
        stage('Checkout GitHub') {
            steps {
                git branch: 'main',
                    credentialsId: 'github-credentials',
                    url: 'https://github.com/fedimbarek/stackUnity.git'
                script {
                    env.SOURCE_BRANCH = sh(script: 'git rev-parse --abbrev-ref HEAD', returnStdout: true).trim()
                    env.COMMIT_SHA = sh(script: 'git rev-parse --short=12 HEAD', returnStdout: true).trim()
                }
            }
        }

        stage('Verifier outils') {
            steps {
                sh '''#!/bin/sh
                    set -eu
                    php -v
                    composer --version
                    node --version
                    npm --version
                    docker --version
                    php -m | grep -qi pdo_mysql
                '''
            }
        }

        stage('Installer dependances') {
            steps {
                sh '''#!/bin/sh
                    set -eu
                    composer validate --no-check-publish
                    composer install --prefer-dist --no-progress --no-interaction
                    npm ci --no-audit --no-fund
                '''
            }
        }

        stage('Preparer Laravel CI') {
            steps {
                sh '''#!/bin/sh
                    set -eu
                    cp .env.example .env
                    php artisan key:generate --force
                    php artisan optimize:clear
                    mkdir -p build
                '''
            }
        }

        stage('Verifier base MySQL CI') {
            steps {
                sh '''#!/bin/sh
                    set -eu
                    php -r '
                    $dsn="mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE");
                    for ($i=0; $i<30; $i++) {
                        try {
                            new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD"), [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
                            echo "MySQL CI disponible\\n";
                            exit(0);
                        } catch (Throwable $e) { sleep(2); }
                    }
                    fwrite(STDERR,"MySQL CI indisponible\\n");
                    exit(1);
                    '
                '''
            }
        }

        stage('Qualite PHP') {
        steps {
            sh '''
                vendor/bin/pint --test || true
            '''
        }
    }

        stage('Tous les tests Laravel') {
            steps {
                sh '''
                    set -eu
                    php artisan config:clear
                    APP_LOCALE=fr APP_FALLBACK_LOCALE=fr php artisan test --log-junit build/all-junit.xml
                '''
            }
        }

        stage('Build assets Vite') {
            steps {
                sh 'npm run build'
            }
        }

        stage('Construire image Docker') {
            steps {
                sh '''#!/bin/sh
                    set -eu
                    docker build -f Dockerfile \
                      -t "$DOCKER_IMAGE:$COMMIT_SHA" \
                      -t "$DOCKER_IMAGE:latest" .
                '''
            }
        }

        stage('Publier Docker Hub') {
            steps {
                withCredentials([usernamePassword(
                    credentialsId: 'meddhia1rom',
                    usernameVariable: 'DOCKER_USER',
                    passwordVariable: 'DOCKER_TOKEN'
                )]) {
                    sh '''#!/bin/sh
                        set -eu
                        set +x
                        echo "$DOCKER_TOKEN" | docker login -u "$DOCKER_USER" --password-stdin
                        docker push "$DOCKER_IMAGE:$COMMIT_SHA"
                        docker push "$DOCKER_IMAGE:latest"
                        docker logout
                    '''
                }
            }
        }

        stage('Declencher deploiement Render') {
            when {
                expression { params.DEPLOY_RENDER && env.SOURCE_BRANCH == 'main' }
            }
            steps {
                withCredentials([string(credentialsId: 'render-deploy-hook', variable: 'RENDER_HOOK')]) {
                    sh '''#!/bin/sh
                        set -eu
                        set +x
                        curl --fail --silent --show-error --retry 3 \
                          --request POST "$RENDER_HOOK"
                    '''
                }
                echo 'Render a accepte la demande de deploiement (verifier son statut sur Render).'
            }
        }
    }

    post {
        always {
            junit testResults: 'build/all-junit.xml', allowEmptyResults: true
            archiveArtifacts artifacts: 'build/*.xml,public/build/**', allowEmptyArchive: true
            sh 'rm -f .env || true'
        }
        success { echo 'CI StackUnity reussie.' }
        failure { echo 'Pipeline en echec : voir Console Output.' }
    }
}
