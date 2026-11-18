-- migrate:up
ALTER TABLE `users` MODIFY `password` varchar(255) NOT NULL DEFAULT '';

-- migrate:down
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Irreversible: password_hash() hashes are longer than 40 characters';
