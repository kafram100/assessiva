# Project Context

This system is an AI-powered Question Bank and Exam Preparation Platform (Novagrade). 
Students are now referred to as **Users**. 
There are NO lecturers in this system.

## Roles
- **Super Admin**: Highest level of authority. Can manage admins and grant permissions.
- **Admin**: Granted permissions by the Super Admin. Has the ability to load questions into the question bank, delete questions, and manage the platform.
- **User**: Creates an account, logs in, and searches for topic-specific questions to solve as preparation for their exams.

## Core AI Workflow
1. A User searches for questions about a specific topic at a certain difficulty level.
2. The AI checks the existing Question Bank to see if there are available questions.
3. **If questions are available**: It pulls them for the user. If there are not enough questions to meet the requested amount, it pulls what is available and generates the remainder.
4. **If no questions are available**: The AI generates the questions dynamically based on the requested topic and difficulty level.
5. All AI-generated questions (including their solutions) are permanently stored in the Question Bank.
6. When another User requests the same topic, the system retrieves the stored questions, reshuffles them, and presents them to the User.

## Billing & Free Tier
- **Free Trial**: Every new User is granted exactly 20 free questions upon registration.
- **Question Mixture**: These 20 free questions must consist of a mixture of multiple-choice questions (MCQs), fill-in-the-blanks, short answers, and theory questions.
- **Paywall**: Once the User has solved their 20 free questions, they hit a paywall and will be charged for any subsequent questions they want to solve.
