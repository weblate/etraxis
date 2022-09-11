SHELL=/bin/bash

.PHONY: help
.PHONY: build
.PHONY: run
.PHONY: cloc
.PHONY: update

help:
	@echo "make build	Builds the project from scratch"
	@echo "make run	Runs the project"
	@echo "make cloc	Count lines of source code in the project"
	@echo "make update	Updates Symfony framework"

build:
	composer install

run:
	symfony serve

cloc:
	cloc ./src

update:
	composer update "symfony/*"
