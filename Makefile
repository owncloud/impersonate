SHELL := /bin/bash

COMPOSER_BIN := $(shell command -v composer 2> /dev/null)

HANDLEBARS=$(CURDIR)/node_modules/handlebars/bin/handlebars
appname=$(notdir $(CURDIR))


# composer
acceptance_test_deps=vendor-bin/behat/vendor

occ=$(CURDIR)/../../occ
private_key=$(HOME)/.owncloud/certificates/$(appname).key
certificate=$(HOME)/.owncloud/certificates/$(appname).crt
sign=php -f $(occ) integrity:sign-app --privateKey="$(private_key)" --certificate="$(certificate)"
sign_skip_msg="Skipping signing, either no key and certificate found in $(private_key) and $(certificate) or occ can not be found at $(occ)"
template_src=$(wildcard js/templates/*.handlebars)
app_namespace=Impersonate
js_namespace=OCA.$(app_namespace)
NODE_PREFIX=$(shell pwd)

NPM := $(shell command -v npm 2> /dev/null)

ifneq (,$(wildcard $(private_key)))
ifneq (,$(wildcard $(certificate)))
ifneq (,$(wildcard $(occ)))
	CAN_SIGN=true
endif
endif
endif

# bin file definitions
PHPUNIT=php -d zend.enable_gc=0  "$(PWD)/../../lib/composer/bin/phpunit"
PHPUNITDBG=phpdbg -qrr -d memory_limit=4096M -d zend.enable_gc=0 "$(PWD)/../../lib/composer/bin/phpunit"
PHP_CS_FIXER=php -d zend.enable_gc=0 vendor-bin/owncloud-codestyle/vendor/bin/php-cs-fixer
PHAN=php -d zend.enable_gc=0 vendor-bin/phan/vendor/bin/phan
PHPSTAN=php -d zend.enable_gc=0 vendor-bin/phpstan/vendor/bin/phpstan
BEHAT_BIN=vendor-bin/behat/vendor/bin/behat

all: build

# start with displaying help
help: ## Show this help message
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//' | sed -e 's/  */ /' | column -t -s :

#
# Catch-all rules
#
.PHONY: dist
dist: ## Build distribution and create tar file impersonate.tar.gz
dist: build-dep js-templates build-src

%.js: $(template_src)
		$(HANDLEBARS) -n "$(js_namespace)" $* -f $@

.PHONY: build-dep
build-dep: ## fetch all dependencies
build-dep: node_modules

node_modules: package.json package-lock.json
		$(NPM) install --prefix=$(NODE_PREFIX) && touch $@

PHONY: js-templates
js-templates: ## build templates for frontend
js-templates: $(addsuffix .js, $(template_src))

# Installs and updates the composer dependencies.
.PHONY: composer
composer:
	composer install --prefer-dist
	composer update --prefer-dist

# Fetches the PHP and JS dependencies and compiles the JS. If no composer.json
# is present, the composer step is skipped, if no package.json or js/package.json
# is present, the npm step is skipped
.PHONY: build
build:
ifneq (,$(wildcard $(CURDIR)/composer.json))
	make composer
endif

.PHONY: clean
clean: ## Clean
clean: clean-build clean-deps

.PHONY: clean-deps
clean-deps:
	rm -Rf vendor
	rm -Rf vendor-bin/**/vendor vendor-bin/**/composer.lock

.PHONY: clean-templates
clean-templates:
		rm -f $(addsuffix .js, $(template_src))

.PHONY: build-src

#
# build source package
#
build-src: ## Build source package
	mkdir -p build/$(appname)
	cp --parents -r \
		appinfo \
		controller \
		css \
		img \
		js \
		l10n \
		lib \
		README.md \
		LICENSE \
		CHANGELOG.md \
		templates \
		build/$(appname)
	rm -Rf build/$(appname)/js/templates/*.handlebars build/$(appname)/l10n/.tx
ifdef CAN_SIGN
	$(sign) --path="$(CURDIR)/build/$(appname)"
else
	@echo $(sign_skip_msg)
endif
	tar --format=gnu -czf build/$(appname).tar.gz -C $(CURDIR)/build/$(appname) ../$(appname)

.PHONY: clean-build
clean-build:
		rm -fR build
		rm -f $(addsuffix .js, $(template_src))
		rm -fr $(NODE_PREFIX)/node_modules


##---------------------
## Tests
##---------------------

.PHONY: test-php-unit
test-php-unit: ## Run php unit tests
test-php-unit:
	$(PHPUNIT) --configuration ./phpunit.xml --testsuite unit

.PHONY: test-php-unit-dbg
test-php-unit-dbg: ## Run php unit tests using phpdbg
test-php-unit-dbg:
	$(PHPUNITDBG) --configuration ./phpunit.xml --testsuite unit

.PHONY: test-php-style
test-php-style: ## Run php-cs-fixer and check owncloud code-style
test-php-style: vendor-bin/owncloud-codestyle/vendor
	$(PHP_CS_FIXER) fix -v --diff --allow-risky yes --dry-run

.PHONY: test-php-style-fix
test-php-style-fix: ## Run php-cs-fixer and fix code style issues
test-php-style-fix: vendor-bin/owncloud-codestyle/vendor
	$(PHP_CS_FIXER) fix -v --diff --allow-risky yes

.PHONY: test-php-phan
test-php-phan: ## Run phan
test-php-phan: vendor-bin/phan/vendor
	$(PHAN) --config-file .phan/config.php --require-config-exists

.PHONY: test-php-phpstan
test-php-phpstan: ## Run phpstan
test-php-phpstan: vendor-bin/phpstan/vendor
	$(PHPSTAN) analyse --memory-limit=4G --configuration=./phpstan.neon --no-progress --level=5 appinfo controller lib

.PHONY: test-acceptance-api
test-acceptance-api: ## Run API acceptance tests
test-acceptance-api: $(acceptance_test_deps)
	BEHAT_BIN=$(BEHAT_BIN) ../../tests/acceptance/run.sh --remote --type api

.PHONY: test-acceptance-cli
test-acceptance-cli: ## Run CLI acceptance tests
test-acceptance-cli:
	BEHAT_BIN=$(BEHAT_BIN) ../../tests/acceptance/run.sh --remote --type cli

.PHONY: test-acceptance-webui
test-acceptance-webui:  ## Run webUI acceptance tests
test-acceptance-webui: $(acceptance_test_deps)
	BEHAT_BIN=$(BEHAT_BIN) ../../tests/acceptance/run.sh --remote --type webUI


#
# Dependency management
#--------------------------------------

composer.lock: composer.json
	@echo composer.lock is not up to date.

vendor: composer.lock
	$(COMPOSER_BIN) install --no-dev

vendor/bamarni/composer-bin-plugin: composer.lock
	$(COMPOSER_BIN) install

vendor-bin/owncloud-codestyle/vendor: vendor/bamarni/composer-bin-plugin vendor-bin/owncloud-codestyle/composer.lock
	$(COMPOSER_BIN) bin owncloud-codestyle install --no-progress

vendor-bin/owncloud-codestyle/composer.lock: vendor-bin/owncloud-codestyle/composer.json
	@echo owncloud-codestyle composer.lock is not up to date.

vendor-bin/phan/vendor: vendor/bamarni/composer-bin-plugin vendor-bin/phan/composer.lock
	$(COMPOSER_BIN) bin phan install --no-progress

vendor-bin/phan/composer.lock: vendor-bin/phan/composer.json
	@echo phan composer.lock is not up to date.

vendor-bin/phpstan/vendor: vendor/bamarni/composer-bin-plugin vendor-bin/phpstan/composer.lock
	$(COMPOSER_BIN) bin phpstan install --no-progress

vendor-bin/phpstan/composer.lock: vendor-bin/phpstan/composer.json
	@echo phpstan composer.lock is not up to date.

vendor-bin/behat/vendor: vendor/bamarni/composer-bin-plugin vendor-bin/behat/composer.lock
	$(COMPOSER_BIN) bin behat install --no-progress

vendor-bin/behat/composer.lock: vendor-bin/behat/composer.json
	@echo behat composer.lock is not up to date.
