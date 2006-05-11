# This makefile generates the documentation for PapersDB.

.PHONY: doc

doc: doxygen.cfg
	doxygen doxygen.cfg

clean:
	$(RM) -r doc/html
