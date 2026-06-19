# Agent Instructions

## Goal
To build and maintain the AI-powered Question Bank preparation platform (Novagrade).

## Key Directives
- **Terminology**: Always use "User" instead of "Student". Do not build or maintain features for "Lecturers" (the lecturer role has been completely removed).
- **Roles & Permissions**: Ensure there is a clear distinction between Super Admin, Admin, and User. Admin capabilities must be configurable by the Super Admin (e.g., loading and deleting questions).
- **AI Generation Logic**: The system must ALWAYS query the database (Question Bank) first before calling the AI generation service. If there is a shortfall, generate new questions, store them in the database for future use, and then serve the combined reshuffled list to the User.
- **Reshuffling**: Question sets for the same topic must be reshuffled for different users to ensure a unique preparation experience.
- **Solutions**: All questions generated and stored must include their corresponding solutions to help users learn.

## Current Focus
Transitioning the current codebase into the new User-centric Question Bank model without breaking existing functionality until a safe migration path is planned. No actual code changes are to be made until the architecture is approved.
