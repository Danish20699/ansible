# 📋 PROJECT CONTEXT & MASTER BLUEPRINT

## 📌 Project Overview
* **Project Name:** Portfolio Ansible Automation
* **Target Repository:** `portfolio-ansible-automation`
* **Engineer / Author:** Danish Nazir (`@Danish20699`) | MLOps Batch 2
* **Primary Objective:** Convert the manual shell automation script (`setup_portfolio.sh` from repo `portfolio-shell-automation`) into a production-grade, declarative, and idempotent **Ansible Playbook orchestration** deploying a multi-tier **LAPP Stack** (Linux, Apache, PostgreSQL, PHP) dynamic portfolio website across managed Linux virtual machines.

---

## 🏗️ Repository Architecture (Tutor's Specification)

The project directory MUST adhere strictly to the following tree structure:

```text
portfolio-ansible-automation/
├── inventory.ini        # Target VM host definitions, IPs, SSH user, Python interpreter
├── setup.yml            # Complete LAPP stack provisioning & deployment playbook
├── destroy.yml          # Complete environment teardown & purge playbook
├── context.md           # This project memory and architectural blueprint
├── README.md            # Public GitHub project overview and usage documentation
└── files/               # Static and dynamic application assets
    ├── index.php        # Dynamic PHP portfolio application
    └── init.sql         # PostgreSQL database schema, tables, and seed records
```

---

## 🧠 Core Concept: Fully Qualified Collection Names (FQCN)

Starting with **Ansible 2.10+**, Ansible separated modules into collections. Built-in core modules belong to the `ansible.builtin` collection. 

To follow modern enterprise DevOps standards, this project strictly uses **FQCN** instead of legacy short module names:

| Legacy Short Name | Modern FQCN Standard | Purpose in Project |
| :--- | :--- | :--- |
| `apt:` | `ansible.builtin.apt` | Installs system packages (`apache2`, `postgresql`, `php`, `php-pgsql`) |
| `service:` | `ansible.builtin.service` | Starts, enables on boot, and restarts system daemons |
| `copy:` | `ansible.builtin.copy` | Transfers `index.php` and `init.sql` from Control Node to VMs |
| `file:` | `ansible.builtin.file` | Manages file states (removes default `/var/www/html/index.html`) |
| `blockinfile:` | `ansible.builtin.blockinfile` | Safely injects DB environment variables into `/etc/apache2/envvars` |
| `command:` / `shell:` | `ansible.builtin.command` / `ansible.builtin.shell` | Executes administrative `psql` database commands as `postgres` |

---

## 🌐 Target Infrastructure & Environment

* **Control Node:** Ubuntu Linux on WSL (Windows 11)
* **Target Managed Nodes:** Oracle VirtualBox Ubuntu Server VMs
  * **`vm1`:** `192.168.56.101` (SSH user: `danish`, passwordless `sudo` enabled)
  * **`vm2`:** `192.168.56.102` (SSH user: `danish`, passwordless `sudo` enabled)
* **Inventory Configuration (`inventory.ini`):**
  ```ini
  [webservers]
  vm1 ansible_host=192.168.56.101
  vm2 ansible_host=192.168.56.102

  [webservers:vars]
  ansible_user=danish
  ansible_python_interpreter=/usr/bin/python3
  ```

---

## ⚙️ Playbook 1: `setup.yml` (Provision & Deploy)

`setup.yml` automates what `setup_portfolio.sh` did manually. It runs with `become: yes` and executes the following sequential tasks:

1. **Install Packages (`ansible.builtin.apt`):**
   * Installs: `apache2`, `postgresql`, `postgresql-contrib`, `php`, `libapache2-mod-php`, `php-pgsql` with `update_cache: yes`.
2. **Start & Enable Services (`ansible.builtin.service`):**
   * Uses loop to ensure both `apache2` and `postgresql` are `state: started` and `enabled: yes`.
3. **Configure Database User & DB (`ansible.builtin.command` / `shell`):**
   * Runs as `become_user: postgres`.
   * Checks and creates role `portfolio_user` with password `'danish1p'`.
   * Creates database `portfolio_db` with owner `portfolio_user`.
   * Grants all privileges on database `portfolio_db` to `portfolio_user`.
4. **Deploy & Seed Schema (`ansible.builtin.copy` + `ansible.builtin.command`):**
   * Copies `files/init.sql` to `/tmp/portfolio_init.sql`.
   * Executes `psql -d portfolio_db -f /tmp/portfolio_init.sql` as `postgres` user.
   * Removes temporary `/tmp/portfolio_init.sql`.
5. **Deploy Application Files (`ansible.builtin.file` + `ansible.builtin.copy`):**
   * Removes default static `/var/www/html/index.html` (`state: absent`).
   * Copies `files/index.php` (and any related php views) to `/var/www/html/` with `owner: www-data`, `group: www-data`, `mode: '0755'`.
6. **Configure Apache DB Environment (`ansible.builtin.blockinfile`):**
   * Appends database connection credentials (`PGHOST`, `PGPORT`, `PGUSER`, `PGPASSWORD`, `PGDATABASE`) into `/etc/apache2/envvars`.
7. **Restart Web Server (`ansible.builtin.service`):**
   * Restarts `apache2` to reload modules and environment variables.

---

## 💣 Playbook 2: `destroy.yml` (Teardown & Purge)

`destroy.yml` represents the automated inverse of `setup.yml` (equivalent to `purge_portfolio.sh`):

1. **Stop Services (`ansible.builtin.service`):**
   * Stops `apache2` and `postgresql`.
2. **Drop Database & User (`ansible.builtin.command`):**
   * Runs as `become_user: postgres`.
   * Terminates active connections and drops database `portfolio_db`.
   * Drops role `portfolio_user`.
3. **Wipe Application Files & Configs (`ansible.builtin.file` + `blockinfile`):**
   * Removes all deployed `.php` files from `/var/www/html/`.
   * Removes the Ansible-managed block from `/etc/apache2/envvars`.
4. **Clean Slate Confirmation:**
   * Leaves the target node clean and ready for a fresh re-provisioning run.

---

## 📂 Source Payloads (`files/`)

* **`files/index.php`:** Dynamic PHP application connecting to PostgreSQL using environment variables, displaying personal profile, live system metrics, and dynamically querying database tables for skills, projects, and education.
* **`files/init.sql`:** DDL & DML script containing:
  * Table definitions: `skills`, `projects`, `education`.
  * Sample records insertion.
  * Schema-level grant: `GRANT ALL ON ALL TABLES IN SCHEMA public TO portfolio_user;`

---

## 🚀 Execution Commands

```bash
# 1. Syntax validation
ansible-playbook -i inventory.ini setup.yml --syntax-check

# 2. Deploy infrastructure & application
ansible-playbook -i inventory.ini setup.yml

# 3. Verify in browser or curl
curl -I http://192.168.56.101/index.php

# 4. Teardown / Purge infrastructure
ansible-playbook -i inventory.ini destroy.yml
```

