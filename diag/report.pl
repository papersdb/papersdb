#!/usr/bin/perl -w

#------------------------------------------------------------------------------
#
# Name: $Id: report.pl,v 1.4 2007/03/06 22:52:42 aicmltec Exp $
#
# See $USAGE.
#
#------------------------------------------------------------------------------

use strict;
use DBI;
use Data::Dumper;

my @tier1venues = qw(AIJ AAAI IJCAI ICML NIPS JAIR AIJ MLJ Bioinformatics
NAR JMLR UAI CCR);

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

sub getPubs {
    my $authors = shift;
    my $startdate = shift;
    my $enddate = shift;
    my $tier1only = shift;
    my $statement;

    $statement = 'SELECT publication.pub_id, publication.title FROM '
        . 'publication, author, pub_author, venue WHERE ';

    my @list;

    if ((defined @$authors) && ($#$authors >= 0)) {
        my @list;
        foreach my $author (@$authors) {
            push(@list, 'author.name LIKE "%' . $author . '%"');
        }
        $statement .= '(' . join(' OR ', @list) . ') ';
    }

    if ((defined $tier1only) && ($tier1only == 1)) {
        $statement .= 'AND venue.title IN ('
            . join(', ', map { $dbh->quote($_) } @tier1venues) . ') '
            . 'AND publication.venue_id=venue.venue_id '
    }
    else {
        $statement .= 'AND (venue.title NOT IN ('
            . join(', ', map { $dbh->quote($_) } @tier1venues) . ') '
            . 'OR publication.venue_id=NULL) '
    }

    $statement .= 'AND publication.pub_id=pub_author.pub_id '
        . 'AND author.author_id=pub_author.author_id '
        . 'AND publication.published BETWEEN \''
        . $startdate . '\' AND \'' . $enddate . '\'';

    my $rv = $dbh->selectall_hashref($statement, 'pub_id');
    return %$rv;
}

sub getPubAuthors {
    my $pub_id = shift;
    my $authors = shift;

    my $statement = 'SELECT author.name FROM '
        . 'publication, author, pub_author WHERE '
        . 'publication.pub_id=' . $pub_id . ' AND '
        . 'publication.pub_id=pub_author.pub_id AND '
        . 'author.author_id=pub_author.author_id AND ';

    if ((defined @$authors) && ($#$authors >= 0)) {
        my @list;
        foreach my $author (@$authors) {
            push(@list, 'author.name LIKE "%' . $author . '%"');
        }
        $statement .= '(' . join(' OR ', @list) . ')';
    }

    #print $statement . "\n";

    my @result = @{ $dbh->selectall_arrayref($statement) };
    print "*******" . Dumper(\@result);
    return @result;
}

my %pubs;
my %pub_authors;
my %pubcount;

print "Tier-1 Venues: " . join(", ", @tier1venues) . "\n\n";

print "AUTHOR;TIME PERIOD;T1 PUBS;NON T1 PUBS\n";

foreach my $year (sort keys %years) {
    foreach my $t1 (qw(0 1)) {
        %pubs = getPubs(\@pi_authors, $years{$year}[0], $years{$year}[1], $t1);

        foreach my $pub_id (sort keys %pubs) {
            my $authors = getPubAuthors($pub_id, \@pi_authors);
            $pubs{$pub_id}{'authors'} = \@{ $authors };
        }

        print Dumper(%pubs);
    }
}

$dbh->disconnect();

1;
