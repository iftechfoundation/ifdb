USE ifdb;

INSERT INTO `users` (`id`, `name`, `created`, `email`, `emailflags`, `profilestatus`, `password`, `pswsalt`, `activationcode`, `acctstatus`, `privileges`) VALUES ('0000000000000000', 'Admin', now(), 'ifdbadmin@ifdb.org', '0', 'T', 'be517aef5a4b32b84d8d4b170cdea17144240c93', '5b9b327dc1e326665ecb867f52f05938', '829435c662e1cf9c9f2d93c03df105cf388860f0', 'A', 'A');
