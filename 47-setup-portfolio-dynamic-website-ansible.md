# Lab 47: Setup Dynamic Portfolio Website with Ansible Playbooks

## 1. Objective
The objective of this lab is to automate the end-to-end provisioning and deployment of a dynamic database-driven **LAPP Stack (Linux, Apache, PostgreSQL, PHP)** portfolio application across managed Linux virtual machines using modern **Ansible Playbooks** with **Fully Qualified Collection Names (FQCN)**. This transitions the manual shell automation workflow from `setup_portfolio.sh` into an enterprise-grade Infrastructure as Code (IaC) deployment.

---

## 2. Infrastructure & Environment Setup

| Host | IP Address | OS | SSH User | Role |
| :--- | :--- | :--- | :--- | :--- |
| **Control Node (`localhost`)** | `127.0.0.1` | Ubuntu Linux (WSL) | `danis` | Ansible Controller |
| **Managed Host 1 (`vm1`)** | `192.168.56.101` | Ubuntu Server | `danish` | Web/DB Target Node 1 |
| **Managed Host 2 (`vm2`)** | `192.168.56.102` | Ubuntu Server | `danish` | Web/DB Target Node 2 |

---

## 3. Architecture & FQCN Modules Breakdown

Modern Ansible (2.10+) uses **Fully Qualified Collection Names (FQCN)** to ensure deterministic module execution and avoid namespace conflicts:

| Task / Operation | Module (FQCN) | Parameters Used |
| :--- | :--- | :--- |
| Package Management | `ansible.builtin.apt` | `pkg` (`apache2`, `postgresql`, `php`, `libapache2-mod-php`, `php-pgsql`, `python3-psycopg2`, `acl`), `state: present`, `update_cache: yes` |
| Service Orchestration | `ansible.builtin.service` | `name: "{{ item }}"`, `state: started`, `enabled: yes` |
| PostgreSQL User Management | `community.postgresql.postgresql_user` | `name: "{{ db_user }}"`, `password: "{{ db_pass }}"`, `state: present` |
| PostgreSQL Database Management | `community.postgresql.postgresql_db` | `name: "{{ db_name }}"`, `owner: "{{ db_user }}"`, `state: present` |
| File & Schema Deployment | `ansible.builtin.copy` | `src`, `dest`, `owner: www-data`, `group: www-data`, `mode: '0755'` |
| Database Initialization | `ansible.builtin.command` | `psql -d {{ db_name }} -f /tmp/portfolio_init.sql` |
| Configuration Management | `ansible.builtin.blockinfile` | Injects database environment variables into `/etc/apache2/envvars` |
| Handlers | `ansible.builtin.service` | Restarts Apache only when configuration or application files change |

---

## 4. Playbook Code (`47-setup-portfolio-dynamic-website-ansible.yml`)

```yaml
---
# ==============================================================================
# Playbook: Setup LAPP Stack Portfolio Automation
# Target: All managed nodes in the 'webservers' inventory group
# Privilege Escalation: become: yes (sudo privileges)
# ==============================================================================
- name: Setup Portfolio Automation using Ansible
  hosts: webservers
  become: yes

  vars:
    db_name: "portfolio_db"
    db_user: "portfolio_user"
    db_pass: "danish1p"

  tasks:
    # 1. Install Apache, PostgreSQL, PHP runtime, psycopg2 driver & acl
    - name: 1. Install required system packages
      ansible.builtin.apt:
        pkg:
          - apache2
          - postgresql
          - postgresql-contrib
          - php
          - libapache2-mod-php
          - php-pgsql
          - python3-psycopg2
          - acl
        state: present
        update_cache: yes

    # 2. Start services immediately and enable them on system boot
    - name: 2. Ensure Apache and PostgreSQL services are started and enabled
      ansible.builtin.service:
        name: "{{ item }}"
        state: started
        enabled: yes
      loop:
        - apache2
        - postgresql

    # 3. Create database user idempotently using Ansible PostgreSQL collection
    - name: 3. Create PostgreSQL User (Native community module)
      become_user: postgres
      community.postgresql.postgresql_user:
        name: "{{ db_user }}"
        password: "{{ db_pass }}"
        state: present

    # 4. Create database and assign ownership to portfolio_user
    - name: 4. Create PostgreSQL Database (Native community module)
      become_user: postgres
      community.postgresql.postgresql_db:
        name: "{{ db_name }}"
        owner: "{{ db_user }}"
        state: present

    # 5. Stage SQL schema & seed script from control node to remote /tmp
    - name: 5. Copy database initialization schema from files/
      ansible.builtin.copy:
        src: files/init.sql
        dest: /tmp/portfolio_init.sql
        mode: '0644'

    # 6. Execute SQL script to create tables, seed records, and grant permissions
    - name: 6. Execute init.sql schema, tables, and seed records
      ansible.builtin.command: psql -d {{ db_name }} -f /tmp/portfolio_init.sql
      become_user: postgres

    # 7. Clean up temporary SQL staging file
    - name: 7. Clean up temporary init.sql from remote VM
      ansible.builtin.file:
        path: /tmp/portfolio_init.sql
        state: absent

    # 8. Remove Ubuntu's default Apache landing page
    - name: 8. Remove default static index.html
      ansible.builtin.file:
        path: /var/www/html/index.html
        state: absent

    # 9. Copy all PHP application pages to Apache web root with web server ownership
    - name: 9. Deploy PHP portfolio files to Apache web root
      ansible.builtin.copy:
        src: "{{ item }}"
        dest: /var/www/html/
        owner: www-data
        group: www-data
        mode: '0755'
      with_fileglob:
        - "files/*.php"
      notify: Restart Apache

    # 10. Inject database credentials into Apache environment variables safely
    - name: 10. Inject database credentials into Apache environment variables
      ansible.builtin.blockinfile:
        path: /etc/apache2/envvars
        marker: "# {mark} ANSIBLE MANAGED PORTFOLIO ENV VARS"
        block: |
          export PGHOST="localhost"
          export PGDATABASE="{{ db_name }}"
          export PGUSER="{{ db_user }}"
          export PGPASSWORD="{{ db_pass }}"
          export PGPORT="5432"
      notify: Restart Apache

  handlers:
    - name: Restart Apache
      ansible.builtin.service:
        name: apache2
        state: restarted
```

---

## 5. Teardown Playbook (`destroy.yml`)
A complementary `destroy.yml` playbook terminates active database connections, drops the database and user, purges `/var/www/html/` PHP assets, removes Apache environment variable blocks, and restarts the web daemon to restore a pristine state.

---

## 6. Execution & Verification

```bash
# Execute setup playbook
ansible-playbook -i inventory.ini 47-setup-portfolio-dynamic-website-ansible.yml

# Verify HTTP response
curl -I http://192.168.56.101/index.php
```

---

## 7. Key Takeaways
- **Infrastructure as Code:** Replacing manual Bash procedural scripts with declarative Ansible playbooks dramatically improves repeatability and maintainability.
- **FQCN Reliability:** Using `ansible.builtin.*` and `community.postgresql.*` represents modern production standards.
- **Security & Decoupling:** Database credentials are never hardcoded into PHP code; instead, they are injected into Apache's execution environment using `ansible.builtin.blockinfile`.
