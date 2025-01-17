SHELL=/bin/bash

.PHONY: help
.PHONY: build
.PHONY: run
.PHONY: check
.PHONY: cloc
.PHONY: test
.PHONY: coverage
.PHONY: jest
.PHONY: update
.PHONY: watch

help:
	@echo "make build	Builds the project from scratch"
	@echo "make run	Runs the project"
	@echo "make check	Checks the project for coding standards"
	@echo "make cloc	Count lines of source code in the project"
	@echo "make test	Executes PHPUnit tests"
	@echo "make coverage	Executes PHPUnit tests with code coverage"
	@echo "make jest	Executes JavaScript tests"
	@echo "make update	Updates Symfony framework"
	@echo "make watch	Watches for changes in frontend sources"

build:
	composer install
	./bin/console doctrine:database:drop --force --quiet || true
	./bin/console doctrine:database:create
	./bin/console doctrine:migrations:migrate -n
	./bin/console doctrine:fixtures:load --group=prod -n
	./bin/console etraxis:export-enums
	npm install
	npx eslint --fix ./assets/enums
	npm run dev

run:
	symfony serve

check:
	./vendor/bin/php-cs-fixer fix
	npx eslint --fix ./assets ./templates

cloc:
	cloc ./assets ./src ./templates ./tests

test:
	./bin/console doctrine:fixtures:load -n
	./bin/phpunit

coverage:
	./bin/console doctrine:fixtures:load -n
	XDEBUG_MODE=coverage ./bin/phpunit --coverage-html=var/coverage

jest:
	npm test

update:
	composer update "symfony/*" --with-all-dependencies

watch:
	./bin/console etraxis:export-enums
	npx eslint --fix ./assets/enums
	npm run watch
