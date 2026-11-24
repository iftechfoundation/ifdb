-- migrate:up
ALTER TABLE games ADD COLUMN cover_art_description VARCHAR(2000) AFTER coverart;

-- migrate:down
ALTER TABLE games DROP COLUMN cover_art_description;
