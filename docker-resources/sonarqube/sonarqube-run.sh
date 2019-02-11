#! /bin/bash


sonar-scanner \
    -Dsonar.sources=. \
    -Dsonar.host.url=https://sonarqube.libriciel.fr:443
    -Dsonar.projectKey=s2low
    -Dsonar.login=9d1576d31df7e19067e40146a304a4daf9bdd2fd
    -Dsonar.exclusions=vendor/**,components/**,ext/**,test/**,xsd/**,temp/**,public/custom/**,public/javascript/**
    -Dsonar.php.tests.reportPath=coverage-reports/junit.log
    -Dsonar.php.coverage.reportPaths=coverage-reports/clover.log
