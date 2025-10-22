.PHONY: all
all: install build

.PHONY: install
install: composer-install blocks-install

.PHONY: build
build: blocks-build

.PHONY: dev
dev: blocks-dev

.PHONY: composer-install
composer-install:
	composer install

.PHONY: blocks-install
blocks-install:
	corepack enable
	pnpm install

.PHONY: blocks-build
blocks-build:
	corepack enable
	pnpm run build

.PHONY: blocks-dev
blocks-dev:
	corepack enable
	pnpm run start

.PHONY: make-pot
make-pot:
	wp i18n make-pot . languages/jcore-dynamic-archive.pot
