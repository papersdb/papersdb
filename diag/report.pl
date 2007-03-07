#!/usr/bin/perl -w

#------------------------------------------------------------------------------
#
# Name: $Id: report.pl,v 1.5 2007/03/07 02:14:57 loyola Exp $
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

my $dbh = DBI->connect('DBI:mysql:pubDB;host=kingman.cs.ualberta.ca', 'papersdb', '')
    || die "Could not connect to database: $DBI::errstr";

sub getPubs {
    my $authors = shift;
    my $startdate = shift;
    my $enddate = shift;
    my $tier1only = shift;
    my $statement;

    $statement = 'SELECT publication.pub_id, publication.title FROM '
        . 'publication, author, pub_author, venue WHERE ';

    if ((defined @$authors) && ($#$authors >= 0)) {
        my @list;
        foreach my $author (@$authors) {
            push(@list, 'author.name LIKE "%' . $author . '%"');
        }
        $statement .= '(' . join(' OR ', @list) . ') ';
    }

    if ((defined $tier1only) && ($tier1only eq "Y")) {
        $statement .= 'AND venue.title IN ('
            . join(', ', map { $dbh->quote($_) } @tier1venues) . ') ';
    }
    else {
        $statement .= 'AND venue.title NOT IN ('
            . join(', ', map { $dbh->quote($_) } @tier1venues) . ') ';
    }

    $statement .= 'AND publication.pub_id=pub_author.pub_id '
        . 'AND author.author_id=pub_author.author_id '
        . 'AND publication.venue_id=venue.venue_id '
        . 'AND publication.published BETWEEN \''
        . $startdate . '\' AND \'' . $enddate . '\'';

    #print $statement . "\n";

    my %rv = %{ $dbh->selectall_hashref($statement, 'pub_id') };

    if ((defined $tier1only) && ($tier1only eq "N")) {
        $statement = 'SELECT publication.pub_id, publication.title FROM '
            . 'publication, author, pub_author WHERE ';

        if ((defined @$authors) && ($#$authors >= 0)) {
            my @list;
            foreach my $author (@$authors) {
                push(@list, 'author.name LIKE "%' . $author . '%"');
            }
            $statement .= '(' . join(' OR ', @list) . ') ';
        }

        $statement .=  'AND publication.venue_id is NULL '
            . 'AND publication.pub_id=pub_author.pub_id '
            . 'AND author.author_id=pub_author.author_id '
            . 'AND publication.published BETWEEN \''
            . $startdate . '\' AND \'' . $enddate . '\'';

        #print $statement . "\n";

        my %rv2 = %{ $dbh->selectall_hashref($statement, 'pub_id') };
        %rv = (%rv, %rv2);
    }

    return %rv;
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
        $statement .= '(' . join(' OR ', @list) . ') ';
    }
    $statement .= 'ORDER BY author.name';

    #print $statement . "\n";

    my $rv = $dbh->selectall_hashref($statement, 'name');
    return %$rv;
}

my %pubs;
my %author_pubs;

foreach my $year (sort keys %years) {
    foreach my $t1 (qw(Y N)) {
        %pubs = getPubs(\@pi_authors, $years{$year}[0], $years{$year}[1], $t1);

        foreach my $pub_id (sort keys %pubs) {
            my %authors = getPubAuthors($pub_id, \@pi_authors);

            my $num_authors = scalar(keys %authors);
            my $authors = join(':', keys %authors);

            $author_pubs{$year}{$t1}{$authors}{'num_authors'} = $num_authors;
            push(@{ $author_pubs{$year}{$t1}{$authors}{'pubs'} }, $pub_id);
        }
    }
}

#print Dumper(\%author_pubs);

my %totals;

print "Tier-1 Venues: " . join(", ", @tier1venues) . "\n\n"
    . "TIME PERIOD;T1;AUTHORS;NUM AUTHORS;NUM PUBS;PUB IDS\n";

foreach my $year (sort keys %author_pubs) {
    foreach my $t1 (sort keys %{ $author_pubs{$year} }) {
        $totals{$year}{$t1} = 0;
        foreach my $authors (sort keys %{ $author_pubs{$year}{$t1} }) {
            printf "%s - %s;%s;%s;%d;%d;", $years{$year}[0], $years{$year}[1],
                $t1, $authors,
                $author_pubs{$year}{$t1}{$authors}{'num_authors'},
                scalar @{ $author_pubs{$year}{$t1}{$authors}{'pubs'} };
            print "\""
                . join(', ', sort @{ $author_pubs{$year}{$t1}{$authors}{'pubs'} })
                . "\"\n";

            $totals{$year}{$t1}
                += scalar @{ $author_pubs{$year}{$t1}{$authors}{'pubs'} }
        }
    }
}


print "\n\nTIME PERIOD;T1;NUM PUBS\n";
foreach my $year (sort keys %author_pubs) {
    foreach my $t1 (sort keys %{ $author_pubs{$year} }) {
        printf "%s - %s;%s;%d\n", $years{$year}[0], $years{$year}[1],
            $t1, $totals{$year}{$t1};
    }
}

$dbh->disconnect();

1;
