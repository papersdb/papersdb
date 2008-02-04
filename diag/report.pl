#!/usr/bin/perl -w

#------------------------------------------------------------------------------
#
# Name: $Id: report.pl,v 1.28 2008/02/04 21:25:46 loyola Exp $
#
# See $USAGE.
#
#------------------------------------------------------------------------------

use strict;
use File::Basename;
use Getopt::Long;
use DBI;
use Data::Dumper;

my $SCRIPTNAME = basename ($0);

my $USAGE = <<USAGE_END;
Usage: $SCRIPTNAME [options]

  Queries the AICML Papers Database and gathers publication statistics for the
  centre's principal investigators, post doctoral fellows and students.

USAGE_END

my $debugSql = 0;

my %years = (0 => ['2002-09-01', '2003-08-31'],
             1 => ['2003-09-01', '2004-08-31'],
             2 => ['2004-09-01', '2006-03-31'],
             3 => ['2006-04-01', '2007-03-31'],
             4 => ['2007-04-01', '2008-03-31']);

my %pi_authors = ('Bowling, M'    => ['2003-07-01', '2008-03-31'],
                  'Goebel, R'     => ['2002-09-01', '2008-03-31'],
                  'Greiner, R'    => ['2002-09-01', '2008-03-31'],
                  'Holte, R'      => ['2002-09-01', '2008-03-31'],
                  'Schaeffer, J'  => ['2002-09-01', '2008-03-31'],
                  'Schuurmans, D' => ['2003-03-01', '2008-03-31'],
                  'Sutton, R'     => ['2003-09-01', '2008-03-31'],
                  'Szepesv.ri, C' => ['2006-09-01', '2008-03-31']);

my @pdf_authors = ('Botea, A',
                   'Brown, M',
                   'Caetano, T',
                   'Cheng, L',
                   'Engel, Y',
                   'Ghavamzadeh, M',
                   'Kirshner, S',
                   'Li, Y',
                   'Ludvig, E',
                   'Madani, O',
                   'Price, B',
                   'Ringlstetter, C',
                   'Southey, F',
                   'Wang, S',
                   'Zheng, T',
                   'Zinkevich, M'
               );

my @student_authors = ('Antonie, M',
                       'Asgarian, N',
                       'Bard, N',
                       'Billings, D',
                       'Botea, A',
                       'Chen, J',
                       'Coulthard, E',
                       'Davison, K',
                       'Dwyer, K',
                       'Farahmand, A',
                       'Fraser, B',
                       'Geramifard, A',
                       'Ghodsi, A',
                       'Guo, Y',
                       'Guo, Z',
                       'Heydari, M',
                       'Hlynka, M',
                       'Hoehn, B',
                       'Huang, J',
                       'Jiao, F',
                       'Johanson, M',
                       'Joyce, B',
                       'Kaboli, A',
                       'Kan, M',
                       'Kapoor, A',
                       'Koop, A',
                       'Lee, C',
                       'Lee, M',
                       'Levner, I',
                       'Li, L',
                       'Lizotte, D',
                       'Lu, Z',
                       'McCracken, P',
                       'Milstein, A',
                       'Morris, M',
                       'Neufeld, J',
                       'Newton, J',
                       'Niu, Y',
                       'Paduraru, C',
                       'Poulin, B',
                       'Rafols, E',
                       'Schauenberg, T',
                       'Schmidt, M',
                       'Silver, D',
                       'Singh, A',
                       'Tanner, B',
                       'Wang, P',
                       'Wang, Q',
                       'Wang, T',
                       'Wang, Y',
                       'White, A',
                       'Wilkinson, D',
                       'Wu, J',
                       'Wu, X',
                       'Xiao, G',
                       'Xu, L',
                       'Zhang, Q',
                       'Zheng, T',
                       'Zhu, T');

my @staff_authors = ('Arthur, R',
                     'Asgarian, N',
                     'Baich, T',
                     'Block, D',
                     'Coghlan, B',
                     'Coulthard, E',
                     'Coulthard, E',
                     'Dacyk, V',
                     'DeMarco, M',
                     'Duguid, L',
                     'Eisner, R',
                     'Farhangfar, A',
                     'Flatt, A',
                     'Fraser, S',
                     'Grajkowski, J',
                     'Harrison, E',
                     'Hiew, A',
                     'Hoehn, B',
                     'Homaeian, L',
                     'Huntley, D',
                     'Jewell, K',
                     'Koop, A',
                     'Larson, B',
                     'Loh, W',
                     'Loyola, N',
                     'Ma, G',
                     'McMillan, K',
                     'Melanson, A',
                     'Morris, M',
                     'Neufeld, J',
                     'Newton, J',
                     'Nicotra, L',
                     'Pareek, P',
                     'Parker, D',
                     'Paulsen, J',
                     'Poulin, B',
                     'Radkie, M',
                     'Roberts, J',
                     'Shergill, A',
                     'Smith, C',
                     'Sokolsky, M',
                     'Stephure, M',
                     'Thorne, W',
                     'Trommelen, M',
                     'Upright, C',
                     'Vicentijevic, M',
                     'Vincent, S',
                     'Walsh, S',
                     'White, T',
                     'Woloschuk, D',
                     'Young, A',
                     'Zheng, T',
                     'Zhu, T');

my %pi_totals;
my %pub_totals;

my $categoryCriteria
    = '(category.category LIKE "%In Conference%" '
      . 'OR category.category LIKE "%In Journal%" '
      . 'OR category.category LIKE "%In Book%") ';

my $dbh = DBI->connect('DBI:mysql:pubDB;host=kingman.cs.ualberta.ca', 'papersdb', '')
    || die "Could not connect to database: $DBI::errstr";

sub pubCitation {
    my (%pub) = %{$_[0]};

    return join(': ', @{ $pub{'authors'} })   . ". " . $pub{'title'} . ". "
        . $pub{'category'}[0] . ". " . $pub{'published'} . ".\n";
}

sub getPub {
    my $pub_id = shift;
    my $statement;
    my %pub = ();

    $statement = 'SELECT pub_id, title,  published, keywords FROM publication WHERE '
        . 'publication.pub_id="' . $pub_id . '"';
    my %rv = %{ $dbh->selectall_hashref($statement, 'pub_id') };

    if (!%rv) { return %pub; }

    %pub = %{ $rv{$pub_id} };

    $statement = 'SELECT category.cat_id, category.category '
        . 'FROM category, pub_cat '
        . 'WHERE category.cat_id=pub_cat.cat_id '
        . 'AND pub_cat.pub_id="' . $pub_id . '"';
    %rv = %{ $dbh->selectall_hashref($statement, 'cat_id') };

    if (%rv) {
        foreach my $cat_id (keys %rv) {
            push( @{ $pub{'category'} }, $rv{$cat_id}{'category'});
        }
    }

    $statement = 'SELECT author.author_id, author.name FROM pub_author, author '
        . 'WHERE author.author_id=pub_author.author_id '
        . 'AND pub_author.pub_id="' . $pub_id . '" '
        . 'ORDER BY author.name ASC';

    %rv = %{ $dbh->selectall_hashref($statement, 'author_id') };

    if (%rv) {
        foreach my $author_id (sort keys %rv) {
            push (@{ $pub{'authors'} }, $rv{$author_id}{'name'});
        }
    }

    return %pub;
}

sub pubDateValid {
    my $author = shift;
    my $date = shift;

    if (!exists $pi_authors{$author}) {
        die "author " . $author . "not found in \%pi_authors\n";
    }

    return (($date ge $pi_authors{$author}[0])
            && ($date le $pi_authors{$author}[1]));
}

sub getPubsForPeriod {
    my $startdate = shift;
    my $enddate = shift;
    my $statement;

    $statement = 'SELECT DISTINCT publication.pub_id '
        . 'FROM publication, category, pub_cat WHERE '
        . ' category.cat_id=pub_cat.cat_id '
        . 'AND publication.pub_id=pub_cat.pub_id '
        . 'AND ' . $categoryCriteria
        . 'AND publication.published BETWEEN \''
        . $startdate . '\' AND \'' . $enddate . '\'';

    #print $statement . "\n";

    my %rv = %{ $dbh->selectall_hashref($statement, 'pub_id') };
    return %rv;
}

sub getNumPubsForPeriod {
    my $startdate = shift;
    my $enddate = shift;
    my $statement;
    my @list = ();

    $statement = 'SELECT DISTINCT publication.pub_id, publication.title '
        . 'FROM publication, category, pub_cat, author, pub_author WHERE '
        . ' category.cat_id=pub_cat.cat_id '
        . 'AND publication.pub_id=pub_cat.pub_id '
        . 'AND ' . $categoryCriteria;

    foreach my $author (keys %pi_authors) {
        push(@list, 'author.name LIKE "%' . $author . '%"');
    }

    $statement .= 'AND (' . join(' OR ', @list) . ') '
        . 'AND author.author_id=pub_author.author_id '
        . 'AND publication.pub_id=pub_author.pub_id '
        . 'AND publication.keywords LIKE "%machine learning%"'
        . 'AND publication.published BETWEEN \''
        . $startdate . '\' AND \'' . $enddate . '\'';

    if ($debugSql) {
        print "getNumPubsForPeriod: " . $statement . "\n";
    }

    my %rv = %{ $dbh->selectall_hashref($statement, 'pub_id') };
    return scalar(keys %rv);
}

sub getPubAuthors {
    my $pub_id = shift;
    my $authors = shift;

    my $statement = 'SELECT author.name FROM '
        . 'publication, author, pub_author WHERE '
        . 'publication.pub_id=' . $pub_id . ' AND '
        . 'publication.pub_id=pub_author.pub_id AND '
        . 'author.author_id=pub_author.author_id AND ';

    if ((defined @$authors) && (scalar @$authors >= 0)) {
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

sub getPubsWithCriteria {
    my $startdate = shift;
    my $enddate = shift;
    my $tier1only = shift;
    my $statement;

    if ((defined $tier1only) && ($tier1only eq "Y")) {
        $statement = 'SELECT DISTINCT publication.pub_id, '
            . 'publication.title FROM publication, venue, category, pub_cat '
            . 'WHERE publication.rank_id=1 '
            . 'AND publication.venue_id=venue.venue_id AND ';
    }
    elsif ((defined $tier1only) && ($tier1only eq "N")) {
        $statement = 'SELECT DISTINCT publication.pub_id, '
            . 'publication.title FROM publication, venue, category, pub_cat '
            . 'WHERE publication.rank_id!=1 '
            . 'AND publication.venue_id=venue.venue_id AND ';
    }
    elsif (!defined $tier1only) {
        $statement = 'SELECT DISTINCT publication.pub_id, '
            . 'publication.title FROM publication, category, pub_cat WHERE '
    }

    $statement .= $categoryCriteria
        . 'AND (category.cat_id="1" OR category.cat_id="3") '
        . 'AND category.cat_id=pub_cat.cat_id '
        . 'AND publication.pub_id=pub_cat.pub_id '
        . 'AND publication.keywords LIKE \'%machine learning%\' '
        . 'AND publication.published BETWEEN \''
        . $startdate . '\' AND \'' . $enddate . '\'';

    if ($debugSql) {
        print $statement . "\n";
    }

    my %rv = %{ $dbh->selectall_hashref($statement, 'pub_id') };

    # if requested non Tier 1 publications, then we must include the
    # publications with NULL venue_id
    if ((defined $tier1only) && ($tier1only eq "N")) {
        $statement = 'SELECT publication.pub_id, publication.title FROM '
            . 'publication, category, pub_cat WHERE ';

        $statement .= $categoryCriteria
            .  'AND publication.venue_id is NULL '
            . 'AND category.cat_id=pub_cat.cat_id '
            . 'AND publication.pub_id=pub_cat.pub_id '
            . 'AND publication.keywords LIKE \'%machine learning%\' '
            . 'AND publication.published BETWEEN \''
            . $startdate . '\' AND \'' . $enddate . '\'';

        if ($debugSql) {
            print $statement . "\n";
        }

        my %rv2 = %{ $dbh->selectall_hashref($statement, 'pub_id') };
        %rv = (%rv, %rv2);
    }

    return %rv;
}

sub piReport {
    my %pubs;
    my %authors;
    my %author_pubs;

    foreach my $year (sort keys %years) {
        foreach my $t1 (qw(Y N)) {
            %pubs = getPubsWithCriteria($years{$year}[0], $years{$year}[1],
                                        $t1);

            foreach my $pub_id (sort keys %pubs) {
                my %pub = getPub($pub_id);
                my @pub_authors = ();

                foreach my $pub_author (@{ $pub{'authors'} }) {
                    foreach my $pi_author (keys %pi_authors) {
                        if (($pub_author =~ /$pi_author/)
                            && (pubDateValid($pi_author, $pub{'published'}))) {
                            push(@pub_authors, $pub_author);
                        }
                    }
                }

                if (scalar @pub_authors == 0) { next; }

                my $num_authors = scalar(@pub_authors);
                my $authors = join(':', @pub_authors);

                $author_pubs{$year}{$t1}{$authors}{'num_authors'} = $num_authors;
                push(@{ $author_pubs{$year}{$t1}{$authors}{'pubs'} }, $pub_id);

                $pub_totals{$year}{$t1}{$pub_id} = 1;

                push(@{ $authors{$authors}{$t1} }, $pub_id);
                if ($num_authors > 1) {
                    push(@{ $authors{'multiple'}{$t1} }, $pub_id);
                }
            }
        }
    }

    my $statement = 'SELECT group_concat(title SEPARATOR \', \') FROM venue where rank_id=1 ORDER BY title DESC';
    my @row = @{ $dbh->selectrow_arrayref($statement) };

    print "Tier-1 Venues: " . $row[0] . "\n\n"
        . "TIME PERIOD;T1;AUTHORS;NUM AUTHORS;NUM PUBS;PUB IDS\n";

    foreach my $year (sort keys %author_pubs) {
        foreach my $t1 (sort keys %{ $author_pubs{$year} }) {
            $pi_totals{$year}{$t1} = 0;
            foreach my $authors (sort keys %{ $author_pubs{$year}{$t1} }) {
                printf "%s - %s;%s;\"%s\";%d;%d;", $years{$year}[0], $years{$year}[1],
                    $t1, $authors,
                        $author_pubs{$year}{$t1}{$authors}{'num_authors'},
                            scalar @{ $author_pubs{$year}{$t1}{$authors}{'pubs'} };
                print "\""
                    . join(', ', sort @{ $author_pubs{$year}{$t1}{$authors}{'pubs'} })
                        . "\"\n";

                $pi_totals{$year}{$t1}
                    += scalar @{ $author_pubs{$year}{$t1}{$authors}{'pubs'} };
            }
        }
    }

    print "\n\nTIME PERIOD;T1;NUM PUBS\n";
    foreach my $year (sort keys %author_pubs) {
        foreach my $t1 (sort keys %{ $author_pubs{$year} }) {
            printf "%s - %s;%s;%d\n", $years{$year}[0], $years{$year}[1],
                $t1, $pi_totals{$year}{$t1};
        }
    }

    print "\n\nAUTHOR(S);T1;NUM PUBS\n";
    foreach my $authors (sort keys %authors) {
        foreach my $t1 (sort keys %{ $authors{$authors} }) {
            printf "%s;%s;%d\n", $authors, $t1, scalar(@{ $authors{$authors}{$t1} });
        }
    }
}

sub pdfStudentReport {
    my %pubs;
    my %authors;
    my %author_pubs;
    my @pdf_students_staff = (@pdf_authors, @student_authors, @staff_authors);
    my @pi_pdf_students_staff = (keys %pi_authors, @pdf_authors, @student_authors, @staff_authors);
    my $hasStudent;

    foreach my $year (sort keys %years) {
        foreach my $t1 (qw(Y N)) {
            %pubs = getPubsWithCriteria($years{$year}[0], $years{$year}[1],
                                        $t1);

            foreach my $pub_id (sort keys %pubs) {
                my %pub = getPub($pub_id);
                my @pub_authors = ();
                $hasStudent = 0;

                foreach my $pub_author (@{ $pub{'authors'} }) {
                    foreach my $valid_author (@pdf_students_staff) {
                        if ($pub_author =~ /$valid_author/) {
                            push(@pub_authors, $pub_author);
                        }
                    }
                }

                if (scalar @pub_authors == 0) {
                    # skip this publication since it was not made by a PDF,
                    # student or staff
                    #
                    #print "not with student " . $pub_id . "\n"
                    #    . pubCitation(\%pub);
                    next;
                }

                my @pi_authors = ();
                foreach my $pub_author (@{ $pub{'authors'} }) {
                    foreach my $pi_author (keys %pi_authors) {
                        if (($pub_author =~ /$pi_author/)
                            && pubDateValid($pi_author, $pub{'published'})) {
                            push(@pi_authors, $pub_author);
                        }
                    }
                }

                unshift(@pub_authors, @pi_authors);

                my $num_authors = scalar(@pub_authors);
                my $authors = join(':', @pub_authors);

                $author_pubs{$year}{$authors}{'num_authors'} = $num_authors;
                push(@{ $author_pubs{$year}{$authors}{'pubs'} }, $pub_id);

                # only count if not already in hash
                if (!exists $pub_totals{$year}{$t1}{$pub_id}) {
                    $pub_totals{$year}{$t1}{$pub_id} = 1;
                }

                push(@{ $authors{$authors} }, $pub_id);
                if ($num_authors > 1) {
                    push(@{ $authors{'multiple'} }, $pub_id);
                }
            }
        }
    }

    my %totals;

    print "\n\nPublications by PDFs and Students\n\n"
        . "TIME PERIOD;AUTHORS;NUM AUTHORS;NUM PUBS;PUB IDS\n";

    foreach my $year (sort keys %author_pubs) {
        $totals{$year} = 0;
        foreach my $authors (sort keys %{ $author_pubs{$year} }) {
            printf "%s - %s;%s;%d;%d;", $years{$year}[0], $years{$year}[1],
                $authors, $author_pubs{$year}{$authors}{'num_authors'},
                    scalar @{ $author_pubs{$year}{$authors}{'pubs'} };
            print "\""
                . join(', ', sort @{ $author_pubs{$year}{$authors}{'pubs'} })
                    . "\"\n";

            $totals{$year}
                += scalar @{ $author_pubs{$year}{$authors}{'pubs'} }
            }
    }

    print "\n\nTIME PERIOD;NUM PUBS FOR PDF AND STUDENT;TOT PUBS;\"%\"\n";
    foreach my $year (sort keys %author_pubs) {
        my $totPubs = scalar(keys %{ $pub_totals{$year}{'N'} })
            + scalar(keys %{ $pub_totals{$year}{'Y'} });

        printf "%s - %s;%d;%d;%f\n", $years{$year}[0], $years{$year}[1],
            $totals{$year}, $totPubs, ($totals{$year} * 100 / $totPubs);
    }

    print "\n\nAUTHOR(S);NUM PUBS\n";
    foreach my $authors (sort keys %authors) {
        printf "%s;%d\n", $authors, scalar(@{ $authors{$authors} });
    }

    print "\n\nPI with PDF/Student;NUM PUBS\n";
    foreach my $pi_author (sort keys %pi_authors) {
        my $pi_count = 0;
        foreach my $authors (sort keys %authors) {
            if ($authors =~ $pi_author) {
                $pi_count += scalar(@{ $authors{$authors} });
            }
        }
        printf "%s;%d\n", $pi_author, $pi_count;
    }
}

#if (!GetOptions ('noposters' => \$noposters)) {
#    die "ERROR: bad options in command line\n";
#}

piReport();

pdfStudentReport();

print "\n\nPDFs or Students not in Database\n";

my @pdf_students = (@pdf_authors, @student_authors);
foreach my $author (@pdf_students) {
    my $statement = 'SELECT author_id, name FROM author WHERE name like "%'
        . $author . '%"';
    my %rv = %{ $dbh->selectall_hashref($statement, 'author_id') };
    if (scalar(keys %rv) == 0) {
        print $author . "\n";
    }
}

$dbh->disconnect();

1;
