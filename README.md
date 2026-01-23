SQLi Platform
=============

Overview
--------
- Local training platform for SQL injection labs.
- Each lab has Step 1, Step 2, and Practice.
- Progress is tracked automatically in the database.

Points System (current)
-----------------------
- Practice labs award points automatically on successful completion.
- Points are written to `user_points_ledger` and displayed on the CTF page if enabled.
- CTF flag validation exists but can be disabled by setting all challenges to inactive.

Practice flow
-------------
- On success, the lab is marked as completed in `user_progress`.
- Points are awarded via `points_award_for_lab_completion()` in `includes/points.php`.
- Implemented in:
  - `labs/lab1/practice.php`
  - `labs/lab2/practice.php`
  - `labs/lab3/practice.php`
  - `labs/lab4/practice.php`
  - `labs/lab5/practice.php`
