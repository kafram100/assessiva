# Skills & Technical Requirements

## AI Service Integration
- Must be able to query the Question Bank database efficiently by topic and difficulty level.
- Must communicate with the AI Microservice (FastAPI) to generate questions containing the prompt, difficulty level, options, and correct solutions.
- Must implement the "Store and Reshuffle" algorithm: Save generated questions to the DB immediately, and randomize the order of options and questions before serving them to the User.

## Role-Based Access Control (RBAC)
- Implement a hierarchical permission system for Super Admin and Admin.
- Super Admins must have a UI/API to assign explicit granular permissions (e.g., `load_questions`, `delete_questions`, `edit_questions`) to regular Admins.
- Ensure the removal of all Lecturer-specific middleware, routes, and controllers during the refactoring phase.

## User Flow
- User Registration and Authentication.
- Search interface for topics and difficulty levels.
- Quiz-taking interface where Users solve questions and view solutions for preparation.
- Tracking User progress and preparation history.
- **Free Tier Tracking**: Logic to track and enforce the 20-question free limit for new accounts, ensuring a mixture of question types (MCQ, fill-in, short answer, theory).
- **Billing Integration**: Payment gateway or credit system to charge users for solving questions after their free tier is exhausted.
