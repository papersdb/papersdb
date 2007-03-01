#!/usr/bin/perl -w

#------------------------------------------------------------------------------
#
# Name: $Id: report.pl,v 1.1 2007/03/01 00:00:50 aicmltec Exp $
#
# See $USAGE.
#
#------------------------------------------------------------------------------

use strict;
use DBI;
use Data::Dumper;

my @tier1venues = qw(AAAI IJCAI ICML NIPS JAIR AIJ MLJ Bioinformatics NAR);

my %years = (0 => ['2002-09-01', '2003-08-31'],
             1 => ['2003-09-01', '2004-08-31'],
             2 => ['2004-09-01', '2006-03-31'],
             3 => ['2006-04-01', '2007-03-31']);

my @pi_authors = ('Szepesvari, C',
                  'Schuurmans, D',
                  'Schaeffer, J',
                  'Bowling, M',
                  'Goebel, R',
                  'Sutton, R',
                  'Holte, R',
                  'Greiner, R');

my $dbh = DBI->connect('DBI:mysql:pubDB;host=kingman', 'papersdb', '')
    || die "Could not connect to database: $DBI::errstr";

sub getTierOnePubs {
    my $startdate = shift;
    my $enddate = shift;
    my $author = shift;
    my $statement;

    $statement = 'SELECT * FROM '
        . 'publication, author, pub_author, venue WHERE ';

    my @list;

    if (defined $author) {
        foreach my $name (@pi_authors) {
            push(@list, 'author.name LIKE "%' . $name . '%" ');
        }
        $statement .= '(' . join(" OR ", @list) . ') AND ';
    }

    $statement .= 'venue.title IN ('
        . join(', ', map { $dbh->quote($_) } @tier1venues)
        . ') '
        . 'AND publication.pub_id=pub_author.pub_id '
        . 'AND author.author_id=pub_author.author_id '
        . 'AND publication.venue_id=venue.venue_id '
        . 'AND publication.published BETWEEN \''
        . $startdate . '\' AND \'' . $enddate . '\'';

    #print $statement . "\n";

    my $rv = $dbh->selectall_hashref($statement, 'pub_id');
    return %$rv;
}

sub getNonTierOnePubs {
    my $startdate = shift;
    my $enddate = shift;
    my $author = shift;
    my $statement;

    $statement = 'SELECT * FROM '
        . 'publication, author, pub_author, venue WHERE ';

    my @list;

    if (defined $author) {
        foreach my $name (@pi_authors) {
            push(@list, 'author.name LIKE "%' . $name . '%" ');
        }
        $statement .= '(' . join(" OR ", @list) . ') AND ';
    }

    $statement .= 'venue.title NOT IN ('
        . join(', ', map { $dbh->quote($_) } @tier1venues)
        . ') '
        . 'AND publication.pub_id=pub_author.pub_id '
        . 'AND author.author_id=pub_author.author_id '
        . 'AND publication.venue_id=venue.venue_id '
        . 'AND publication.published BETWEEN \''
        . $startdate . '\' AND \'' . $enddate . '\'';

    #print $statement . "\n";

    my $rv = $dbh->selectall_hashref($statement, 'pub_id');
    return %$rv;
}

my %pubs;
my @keys;

foreach my $year (sort keys %years) {
    %pubs = getTierOnePubs($years{$year}[0], $years{$year}[1], 1);

    @keys = keys %pubs;

    print "Tier 1 publications by all PIs for " . $years{$year}[0]
        . " - " . $years{$year}[1]
        . ": " . ($#keys + 1) . "\n";
}

print "\n";

foreach my $year (sort keys %years) {
    %pubs = getNonTierOnePubs($years{$year}[0], $years{$year}[1], 1);

    @keys = keys %pubs;

    print "Non Tier 1 publications by all PIs for " . $years{$year}[0]
        . " - " . $years{$year}[1]
        . ": " . ($#keys + 1) . "\n";
}

print "\n";

foreach my $year (sort keys %years) {
    %pubs = getTierOnePubs($years{$year}[0], $years{$year}[1]);
    @keys = keys %pubs;

    print "Tier 1 publications by all authors: " . ($#keys + 1) . "\n";
}

print "\n";

foreach my $year (sort keys %years) {
    %pubs = getNonTierOnePubs($years{$year}[0], $years{$year}[1]);
    @keys = keys %pubs;

    print "Non Tier 1 publications by all authors: " . ($#keys + 1) . "\n";
}

$dbh->disconnect();

1;
