-- migrate:up
UPDATE persistentsessions SET id = SHA2(id, 256) WHERE LENGTH(id) = 40;

-- migrate:down
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Irreversible: the original session IDs are gone';
