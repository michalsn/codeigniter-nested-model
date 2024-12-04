# Configuration

## Naming convention

This library relies on some conventions.

### Models

- Model names should always have the suffix "Model" in the file name, i.e., a user model would be named `UserModel`.
- Table names are always plural, i.e., the user model should have a `users` table.
- Foreign keys in another table are always the sum of the name of a singular table and the primary key, i.e., if we have a table `users` with a primary key `id`, the foreign key should look like this: `user_id`.

### Entities

- Entities should have the same name as Models that use the entity, but without the “Model” suffix, i.e., the entity for `UserModel` should be `User`.

## Traits

This library relies on traits.

### Models

- Every model should use `HasRelations` trait.

### Entities

- Every entity should use `HasLazyRelations` trait.
