CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_number INT NOT NULL,
    question TEXT NOT NULL,
    choices JSON NOT NULL,
    correct_answer CHAR(1) NOT NULL
);

