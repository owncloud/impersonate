SHELL := /bin/bash

HANDLEBARS=$(CURDIR)/node_modules/handlebars/bin/handlebars
appname=$(notdir $(CURDIR))
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
ifndef NPM
	$(error npm is not available on your system, please install npm)
endif

ifneq (,$(wildcard $(private_key)))
ifneq (,$(wildcard $(certificate)))
ifneq (,$(wildcard $(occ)))
	CAN_SIGN=true
endif
endif
endif

#
# Catch-all rules
#
.PHONY: dist
dist: build-dep js-templates build-src

%.js: $(template_src)
		$(HANDLEBARS) -n "$(js_namespace)" $* -f $@

build-dep:
		make build-dep

build-dep: package.json
		$(NPM) install --prefix $(NODE_PREFIX) && touch $@

PHONY: js-templates
js-templates: $(addsuffix .js, $(template_src))

.PHONY: clean
clean: clean-build

.PHONY: clean-templates
clean-templates:
		rm -f $(addsuffix .js, $(template_src))


.PHONY: build-src

#
# build source package
#
build-src:
ifdef CAN_SIGN
	mkdir -p build/$(appname)
	cp --parents -r \
		appinfo \
		controller \
		css \
		img \
		js \
		l10n \
		lib \
		templates \
		build/$(appname)
	$(sign) --path="$(CURDIR)/build/$(appname)"
	rm -f build/$(appname)/js/templates/*.handlebars
	tar -czf build/$(appname).tar.gz -C $(CURDIR)/build/$(appname) ../$(appname)
else
	@echo $(sign_skip_msg)
endif

.PHONY: clean
clean-build:
		rm -fR build
		rm -f $(addsuffix .js, $(template_src))
		rm -fr $(NODE_PREFIX)/node_modules build-dep
