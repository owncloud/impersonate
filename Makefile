SHELL := /bin/bash

appname=$(notdir $(CURDIR))
occ=$(CURDIR)/../../occ
private_key=$(CURDIR)/$(appname).key
certificate=$(CURDIR)/$(appname).crt
sign=php -f $(occ) integrity:sign-app --privateKey="$(private_key)" --certificate="$(certificate)"

#
# Catch-all rules
#
.PHONY: all
all: build-src

.PHONY: clean
clean: clean-build

.PHONY: build-src

#
# build source package
#
build-src:
		$(sign) --path="$(CURDIR)"
		mkdir -p build
		tar cvzf build/$(appname).tar.gz ../$(appname) \
		--exclude-vcs \
		--exclude="../$(appname)/tests" \
		--exclude="../$(appname)/build" \
		--exclude="../$(appname)/$(appname).csr" \
		--exclude="../$(appname)/$(appname).crt" \
		--exclude="../$(appname)/$(appname).key" \
		--exclude="../$(appname)/Makefile"

.PHONY: clean
clean-build:
		rm -fR build
