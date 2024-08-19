create database if not exists ifarchive;
drop database ifarchive;
create database ifarchive CHARACTER SET latin1 COLLATE latin1_german2_ci;
use ifarchive;

source ifdb.sql

drop table audit;
drop table formatprivs;
drop table games2;
drop table logins;
drop table nonces;
drop table osprivs;
drop table persistentsessions;
drop table privileges;
drop table reviews2;
drop table reviewflags;
drop table userfilters;
drop table stylepics;

alter table clubs
  drop column password,
  drop column pswsalt;

delete from clubmembers
  where (select members_public from clubs
         where clubs.clubid = clubmembers.clubid) != 'Y';

delete from users
  where acctstatus != 'A';

update users
  set picture = concat('https://ifdb.org/showuser?pic&id=', id)
  where picture is not null;

update users
  set profile = null
  where profilestatus = 'R';

update users
  set publicemail = null
  where (emailflags & 1) != 0;

/* drop the sandbox views */
drop view gameRatingsSandbox0;
drop view gameRatingsSandbox01;

drop table gameRatingsSandbox0_mv;
drop trigger reviews_insert;
drop trigger reviews_update;
drop trigger reviews_delete;

/* delete troll reviews and comments */
delete from ucomments
  where userid in (select id from users where sandbox = 1)
  or source = 'U' and sourceid in (select id from users where sandbox = 1)
  or source = 'R' and sourceid in (select id from reviews where userid in (select id from users where sandbox = 1))
  or source = 'L' and sourceid in (select id from reclists where userid in (select id from users where sandbox = 1))
  or source = 'P' and sourceid in (select pollid from polls where userid in (select id from users where sandbox = 1))
;
delete from reviews, reviewtags, reviewvotes
  using reviews
    left outer join reviewtags
      on reviewtags.reviewid = reviews.id
    left outer join reviewvotes
      on reviewvotes.reviewid = reviews.id
  where reviews.userid in (select id from users where sandbox = 1);

delete from reviewvotes where userid in (select id from users where sandbox = 1);
delete from crossrecs where userid in (select id from users where sandbox = 1);
delete from wishlists where userid in (select id from users where sandbox = 1);
delete from playedgames where userid in (select id from users where sandbox = 1);
delete from unwishlists where userid in (select id from users where sandbox = 1);
delete from news where userid in (select id from users where sandbox = 1);
delete from stylesheets where userid in (select id from users where sandbox = 1);
delete from clubmembers where userid in (select id from users where sandbox = 1);

delete from reclists, reclistitems
  using reclists
    left outer join reclistitems
      on reclistitems.listid = reclists.id
  where reclists.userid in (select id from users where sandbox = 1);

delete from polls, pollvotes, pollcomments
  using polls
    left outer join pollvotes
      on pollvotes.pollid = polls.pollid
    left outer join pollcomments
      on pollcomments.pollid = polls.pollid
  where polls.userid in (select id from users where sandbox = 1);

delete from pollvotes where userid in (select id from users where sandbox = 1);

alter table users
  drop column email,
  drop column emailflags,
  drop column profilestatus,
  drop column password,
  drop column pswsalt,
  drop column activationcode,
  drop column acctstatus,
  drop column privileges,
  drop column defaultos,
  drop column defaultosvsn,
  drop column noexedownloads,
  drop column mirrorid,
  drop column stylesheetid,
  drop column offsite_display,
  drop column accessibility,
  drop column caughtupdate,
  drop column remarks,
  drop column tosversion,
  drop column lastlogin,
  drop column sandbox
;

delete from reviews
  where now() < embargodate;

update games
  set coverart = concat('https://ifdb.org/viewgame?coverart&id=', id)
  where coverart is not null;

delete from playedgames
  where (select locate('P', publiclists) from users
         where users.id = playedgames.userid) = 0;

delete from unwishlists
  where (select locate('U', publiclists) from users
         where users.id = unwishlists.userid) = 0;

delete from wishlists
  where (select locate('W', publiclists) from users
         where users.id = wishlists.userid) = 0;

alter table users
  drop column publiclists;

delete from ucomments
  where private is not null;

alter table ucomments
  drop column private;

alter table reviewvotes
  drop column userid;

/* drop table userscoreitems; */
/* drop table userscores; */
/* drop table visreviews; */

drop view gamelinkstats;
drop view gameratings;
drop view gameRatings;
drop view userScores;
drop view userscoreitems;
drop view visreviews;

drop table userScores_mv;
