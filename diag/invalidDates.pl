#!/usr/bin/perl -w

#------------------------------------------------------------------------------
#
# Name: $Id: invalidDates.pl,v 1.1 2007/03/09 17:41:57 aicmltec Exp $
#
# See $USAGE.
#
#------------------------------------------------------------------------------

use strict;
use DBI;
use Data::Dumper;

my $dbh = DBI->connect('DBI:mysql:pubDB;host=kingman.cs.ualberta.ca', 'papersdb', '')
    || die "Could not connect to database: $DBI::errstr";

sub getPubs {
    my $statement;

    $statement = 'SELECT DISTINCT pub_id, published FROM publication';

    return %{ $dbh->selectall_hashref($statement, 'pub_id') };
}

my %pubs = getPubs();

print "bad month:\n";
foreach my $pub_id (sort keys %pubs) {
    if ($pubs{$pub_id}{'published'} =~ /^\d{4}-00-\d{1,2}$/) {
        print $pub_id . " " . $pubs{$pub_id}{'published'} . "\n";
    }
}

print "\nbad day:\n";
foreach my $pub_id (sort keys %pubs) {
    if ($pubs{$pub_id}{'published'} =~ /^\d{4}-\d[1-9]-00$/) {
        print $pub_id . " " . $pubs{$pub_id}{'published'} . "\n";
    }
}


1;
