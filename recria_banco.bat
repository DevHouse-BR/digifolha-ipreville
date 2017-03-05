@ECHO OFF
php bin/doctrine generate-models-yaml
php bin/doctrine build-all-reload --no-confirmation
pause