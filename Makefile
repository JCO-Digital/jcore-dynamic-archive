.PHONY: all
all: install build

.PHONY: install
install: composer-install blocks-install

.PHONY: build
build: blocks-build

.PHONY: dev
dev: blocks-dev

.PHONY: release
release:
	mkdir -p release
	zip release/jcore-dynamic-archive.zip -r * -x @zip_exclude.txt

.PHONY: composer-install
composer-install:
	composer install

.PHONY: blocks-install
blocks-install:
	corepack enable
	cd blocks && pnpm install

.PHONY: blocks-build
blocks-build:
	corepack enable
	cd blocks && pnpm run build

.PHONY: blocks-dev
blocks-dev:
	corepack enable
	cd blocks && pnpm run start

.PHONY: make-pot
make-pot:
	wp i18n make-pot . languages/jcore-dynamic-archive.pot
