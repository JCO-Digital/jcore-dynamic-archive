.PHONY: all install build watch composer-install blocks-install blocks-build blocks-watch

all: install build

install: composer-install blocks-install

build: blocks-build

watch: blocks-watch

composer-install:
	composer install

blocks-install:
	corepack enable
	cd blocks && pnpm install

blocks-build:
	corepack enable
	cd blocks && pnpm build

blocks-watch:
	corepack enable
	cd blocks && pnpm start
