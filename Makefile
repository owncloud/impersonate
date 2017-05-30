SHELL := /bin/bash

appname=$(notdir $(CURDIR))
occ=$(CURDIR)/../../occ
private_key=$(HOME)/.owncloud/certificates/$(appname).key
certificate=$(HOME)/.owncloud/certificates/$(appname).crt
sign=php -f $(occ) integrity:sign-app --privateKey="$(private_key)" --certificate="$(certificate)"
sign_skip_msg="Skipping signing, either no key and certificate found in $(private_key) and $(certificate) or occ can not be found at $(occ)"

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
.PHONY: all
all: build-src

.PHONY: clean
clean: clean-build

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
		templates \
		build/$(appname)
	cp settings-admin.php build/$(appname)
	$(sign) --path="$(CURDIR)/build/$(appname)"
else
	@echo $(sign_skip_msg)
endif
	tar -czf build/$(appname).tar.gz -C $(CURDIR)/build/$(appname) ../$(appname)

.PHONY: clean
clean-build:
		rm -fR build
