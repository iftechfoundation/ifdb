-- migrate:up
ALTER TABLE `logins` MODIFY `ip` varchar(45) NOT NULL DEFAULT '';

-- migrate:down
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Irreversible: narrowing would truncate IPv6 addresses';
