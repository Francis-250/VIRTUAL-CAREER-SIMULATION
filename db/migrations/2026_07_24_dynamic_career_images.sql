USE career_sim;

-- Initial content only. The application reads these values from the database,
-- and content managers can change them without editing PHP.
UPDATE careers SET image='https://images.unsplash.com/photo-1503387762-592deb58ef4e?auto=format&fit=crop&w=900&q=80' WHERE slug='civil-engineer' AND (image IS NULL OR image='');
UPDATE careers SET image='https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=900&q=80' WHERE slug='data-analyst' AND (image IS NULL OR image='');
UPDATE careers SET image='https://images.unsplash.com/photo-1533750349088-cd871a92f312?auto=format&fit=crop&w=900&q=80' WHERE slug='marketing-specialist' AND (image IS NULL OR image='');
UPDATE careers SET image='https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=900&q=80' WHERE slug='registered-nurse' AND (image IS NULL OR image='');
UPDATE careers SET image='https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&w=900&q=80' WHERE slug='software-developer' AND (image IS NULL OR image='');
UPDATE careers SET image='https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=900&q=80' WHERE slug='teacher' AND (image IS NULL OR image='');
