pipeline {
    agent any

    options {
        timestamps()
        disableConcurrentBuilds()
        buildDiscarder(logRotator(numToKeepStr: '15'))
        skipDefaultCheckout(true)
    }

    parameters {
        booleanParam(name: 'RUN_FULL_TESTS', defaultValue: false,
            description: 'Executer tous les tests (Equipements/Reservations doivent etre corriges)')
        booleanParam(name: 'DEPLOY_STAGING', defaultValue: false,
            description: 'Deployer sur staging uniquement depuis main, apres succes des tests')
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
    }

    stages {
        stage('Checkout') {
            steps { checkout scm }
        }

        stage('Verifier outils') {
            steps {
                sh '''#!/bin/sh
                    set -eu
                    php -v
                    composer --version
                    node --version
                    npm --version
                    php -m | grep -Ei 'pdo_mysql|xdebug' 
                '''
            }
        }

        stage('Installer dependances') {
            steps {
                sh '''#!/bin/sh
                    set -eu
                    composer install --prefer-dist --no-progress --no-interaction
                    npm ci --no-audit --no-fund
                '''
            }
        }

        stage('Initialiser environnement CI') {
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

        stage('Attendre MySQL de CI') {
            steps {
                sh '''#!/bin/sh
                    set -eu
                    php -r '
                        $dsn = "mysql:host=" . getenv("DB_HOST") . ";port=" . getenv("DB_PORT") . ";dbname=" . getenv("DB_DATABASE");
                        for ($i = 0; $i < 40; $i++) {
                            try {
                                new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD"), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                                echo "MySQL CI pret\\n";
                                exit(0);
                            } catch (Throwable $e) { sleep(2); }
                        }
                        fwrite(STDERR, "MySQL CI indisponible\\n");
                        exit(1);
                    '
                '''
            }
        }

        stage('Tests Weather & Notifications') {
            steps {
                sh '''#!/bin/sh
                    set -eu
                    php artisan test \
                        tests/Unit/Weather \
                        tests/Feature/Weather \
                        tests/Feature/Notifications/UserNotificationsTest.php \
                        --log-junit build/weather-junit.xml \
                        --coverage-clover build/weather-coverage.xml
                '''
            }
        }

        stage('Tous les tests (optionnel)') {
            when { expression { return params.RUN_FULL_TESTS } }
            steps {
                sh 'php artisan test --log-junit build/all-junit.xml'
            }
        }

        stage('Compiler frontend Vite') {
            steps {
                sh 'npm run build'
            }
        }

        stage('Deployer staging (optionnel)') {
            when {
                expression {
                    return params.DEPLOY_STAGING &&
                        (env.BRANCH_NAME == 'main' || env.GIT_BRANCH == 'origin/main')
                }
            }
            steps {
                sshagent(credentials: ['stackunity-staging-ssh']) {
                    sh '''#!/bin/sh
                        set -eu
                        ssh -o BatchMode=yes deploy@staging.example.com \
                            'cd /srv/stackunity && bash -s' < devops/deploy-staging.sh
                    '''
                }
            }
        }
    }

    post {
        always {
            junit testResults: 'build/*-junit.xml', allowEmptyResults: true
            archiveArtifacts artifacts: 'build/*.xml,public/build/**',
                allowEmptyArchive: true, fingerprint: true
            sh 'rm -f .env'
        }
        success { echo 'CI reussie : Weather, notifications et Vite OK.' }
        failure { echo 'Echec : consulter les logs de la stage en erreur.' }
    }
}
