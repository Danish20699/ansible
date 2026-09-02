# ansible
# 🚀 Ansible Automation & Configuration Management Labs

Welcome to the **Ansible Automation** repository maintained by **Danish Nazir** ([@Danish20699](https://github.com/Danish20699)). 

This repository contains hands-on labs, configuration files, playbooks, and step-by-step guides for automating infrastructure, configuring servers, and managing Linux/Windows nodes at scale using **Ansible**.

---

## 🏗️ Architecture Overview

Ansible operates as an **agentless** automation tool, communicating with remote target machines over standard **SSH**:

```text
+------------------------------------+
|     Ansible Control Node (Linux)   |
|        - ansible / python3         |
|        - hosts.ini (Inventory)     |
|        - SSH Private Key           |
+-----------------+------------------+
                  |
        (SSH / Key Auth)
                  |
    +-------------+-------------+
    |                           |
    v                           v
+-------------------+   +-------------------+
|  Target Node 1    |   |  Target Node 2    |
|  (Web Server)     |   |  (Database)       |
+-------------------+   +-------------------+
