# This makefile generates the documentation for PapersDB.

PHPDOCUMENTOR_PATH := $(HOME)/apps/PhpDocumentor-1.3.0
PHPDOCUMENTOR := $(PHPDOCUMENTOR_PATH)/phpdoc

DIRS := $(patsubst ./%,%,$(shell find . -type d))

.PHONY: docs

docs: default.ini phpDocumentor.ini
	$(PHPDOCUMENTOR) -c default.ini

clean:
	$(RM) -r doc
