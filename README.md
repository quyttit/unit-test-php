# PHP unit-test Project

This project is a PHP-based application with a focus on order processing. Below are the instructions to set up the project, run tests, and navigate the test cases checklist.

## 🛠 Setting Up the Project

Build and start the Docker containers:
   ```bash
   docker-compose up --build
   ```
---

## ✅ Running Tests

1. Access the PHP container:
   ```bash
   docker exec -it php bash
   ```

2. Run the tests and generate coverage using the following command:
   ```bash
   ./vendor/bin/pest --coverage --coverage-html ./reports/coverage
   ```

---

## 📋 Test Case Checklist

Navigate to the `CHECKLIST.md` file to view the list of test cases and their descriptions.

- File location: [`CHECKLIST.md`](./CHECKLIST.md)
- This file contains a detailed checklist of all test cases implemented in the project.
- Additional reports:
  - ![`reports/evd/cli`](./reports/evd/test-cli.jpg)
  - ![`reports/evd/dashboard`](./reports/evd/test-dashboard.jpg)

---

## 📂 Project Structure

- **`docker-compose.yml`**: Docker configuration for the project.
- **`tests/`**: Contains all test cases for the application.
- **`App/`**: Contains the core application logic, including services, models, and interfaces.

---

## 📝 Notes

- Ensure that the `vendor` directory is installed by running `composer install` inside the container if not already present.
- For any issues, please refer to the `CHECKLIST.md` file for test case navigation or raise an issue in the repository.

Happy coding! 🚀# Lab PHP Project

This project is a PHP-based application with a focus on order processing. Below are the instructions to set up the project, run tests, and navigate the test cases checklist.
