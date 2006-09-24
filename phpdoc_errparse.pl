#!/usr/local/bin/perl -w

# Converts PhpDocumentors error / warning reporting into a format that Emacs
# can parse.

use strict;
use File::Basename;
use File::Find;

my $state = 0;
my $file = '';
my $result = 0;

while (<STDIN>) {
    if (/^General Parsing Stage$/) {
        $state = 1;
        print $_;
    }

    if (/^Converting From Abstract Parsed Data$/) {
        $state = 0;
    }

    if ($state == 1) {
        $result = 1;
        chomp;
        if (/^Reading file (.+) -- Parsing file/) {
            $file = $1;
        }

        if (/^(WARNING|ERROR) in (.+) on line (\d+): (.+)/) {
            print "$file: $3: $1: $4\n";
        }
        #print "state: " . $state . " $file: " . $file . " | " . $_;
    }
    else {
        print $_;
    }
}

0;
