SHELL=/bin/bash

.PHONY: help
.PHONY: build
.PHONY: run
.PHONY: cloc
.PHONY: test
.PHONY: coverage
.PHONY: update

help:
	@echo "make build	Builds the project from scratch"
	@echo "make run	Runs the project"
	@echo "make cloc	Count lines of source code in the project"
	@echo "make test	Executes PHPUnit tests"
	@echo "make coverage	Executes PHPUnit tests with code coverage"
	@echo "make update	Updates Symfony framework"

build:
	composer install

run:
	symfony serve

cloc:
	cloc ./src ./tests

test:
	./bin/phpunit

coverage:
	XDEBUG_MODE=coverage ./bin/phpunit --coverage-html=var/coverage

update:
	composer update "symfony/*"
