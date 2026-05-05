# Internal IT Ticketing System

> A centralized digital platform designed to bridge the communication gap between Customer Service and the IT Department, streamlining complaint resolution through data-driven automation.

---

## 🚀 Project Context

In many traditional corporate environments, client complaints are often handled through fragmented channels (emails, manual notes, or chat), leading to slow resolution times and a lack of accountability.

This project **digitizes the entire complaint lifecycle**:
1. **Reporting**: Customer Service (CS) receives a client issue and generates a digital ticket instantly.
2. **Resolution**: The IT Team receives the ticket on their dashboard, allowing for rapid troubleshooting, assignment, and status updates.
3. **Analysis**: Every interaction is recorded, feeding into a specialized **Analytics Page** to identify recurring issues, monitor team workload, and optimize resolution speed.

## ✨ Key Features

- **Automated Ticket Lifecycle**: Real-time status tracking from *Open* to *In Progress* and *Resolved*.
- **CS Portal**: Intuitive interface for Customer Service to log and prioritize client complaints.
- **IT Management Dashboard**: Task management system for developers to track assignments and technical progress.
- **Data Analytics Dashboard**: Visualizes key metrics like ticket volume, average resolution time, and common issue categories.
- **Centralized Database**: Historical data storage for audit trails and performance analysis.
- **Responsive UI**: A modern, minimalist interface designed for high-efficiency internal use.

## 🛠️ Tech Stack

- **Backend**: [Laravel 11.x](https://laravel.com) (PHP 8.2+)
- **Database**: MySQL (Relational schema for optimized data retrieval)
- **Frontend**: [Tailwind CSS](https://tailwindcss.com) (Minimalist & Responsive design)
- **API**: RESTful API Architecture
- **Architecture**: Model-View-Controller (MVC)

## ⚙️ Installation & Setup

### Prerequisites
- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL

### Steps

1. **Clone the Repository**
   ```bash
   git clone [https://github.com/ryanx30/internal-ticketing.git](https://github.com/ryanx30/internal-ticketing.git)
   cd internal-ticketing
Install Dependencies

2. 
   ```bash
   composer install
   npm install && npm run build
   Environment Setup

3. 
   ```Bash
   cp .env.example .env
   php artisan key:generate

   *Note: Update your database credentials in the `.env` file.*

4. **Run Migrations & Seeders**
   ```bash
   php artisan migrate --seed
Start Development Server

5. 
   ```bash
   php artisan serve


## 📊 Analytics & Insights

The system focuses on data-driven decision making by tracking:
- **Response Efficiency**: Monitoring the time taken from ticket creation to IT resolution.
- **Ticket Distribution**: Identifying the most common technical bottlenecks for clients.
- **Workload Management**: Analyzing ticket distribution among the IT team members.

## 📄 License

Distributed under the MIT License.

---

## 👤 Author

**Muhammad Rahadian**
- **GitHub**: [ryanx30](https://github.com/ryanx30)
- **LinkedIn**: [Muhammad Rahadian](https://www.linkedin.com/in/muhammadrahadian/)