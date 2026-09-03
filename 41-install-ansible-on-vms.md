# Lab 41: Ansible Installation & VM Configuration Guide

## 🎯 Objective
To install and configure **Ansible** on a Linux Control Node (Ubuntu / WSL), establish secure passwordless SSH key authentication to managed Virtual Machines (VMs), configure an inventory file (`hosts.ini`), and verify end-to-end connectivity using the Ansible `ping` module.

---

## 🏗️ Architecture Overview

Ansible operates as a **push-based, agentless** configuration management tool:

```text
+-------------------------------------------------------------------+
|               ANSIBLE CONTROL NODE (WSL / Ubuntu)                 |
|  - OS: Ubuntu 24.04 / 26.04 LTS (WSL)                             |
|  - Software: Ansible Core, Python 3                               |
|  - Config: ansible.cfg                                            |
|  - Inventory: hosts.ini                                           |
|  - SSH Identity: ~/.ssh/id_ed25519 (Private Key)                 |
+---------------------------------+---------------------------------+
                                  |
                   SSH Connection (Port 22 / 2222)
                   Passwordless Public Key Auth
                                  |
        +-------------------------+-------------------------+
        |                                                   |
        v                                                   v
+-------------------------------+   +-------------------------------+
|    MANAGED NODE 1 (Web VM)    |   |     MANAGED NODE 2 (DB VM)    |
|  - IP / Port: 127.0.0.1:2222  |   |  - IP: 192.168.1.135:22       |
|  - OpenSSH Server running     |   |  - OpenSSH Server running     |
|  - Python 3 installed         |   |  - Python 3 installed         |
|  - ~/.ssh/authorized_keys     |   |  - ~/.ssh/authorized_keys     |
+-------------------------------+   +-------------------------------+
```

### Why Agentless?
Unlike tools like Puppet or Chef, Ansible requires **no agent software or background daemons** installed on target machines. All orchestration and configuration tasks run over standard **OpenSSH** using Python.

---

## 📋 Prerequisites

- **Control Node:** Ubuntu 22.04+ (WSL or VM) with Python 3 and `sudo` privileges.
- **Managed Nodes:** 1 or more Linux target VMs with OpenSSH server running (`sudo systemctl status ssh`).
- **Network:** Reachable IP / port forwarding (e.g., VirtualBox NAT `127.0.0.1:2222` or Bridged IP `192.168.1.x`).

---

## 🛠️ Step-by-Step Implementation

### Step 1: Update Package Lists on Control Node (WSL)

In your WSL terminal:
```bash
sudo apt update && sudo apt upgrade -y
```
* **Why:** Ensures package indices and system dependencies are up-to-date.

---

### Step 2: Install Ansible on the Control Node

```bash
# Install common software management tools
sudo apt install -y software-properties-common curl git

# Add the official Ansible PPA
sudo add-apt-repository --yes --update ppa:ansible/ansible

# Install Ansible
sudo apt install -y ansible
```

---

### Step 3: Verify Ansible Installation

```bash
ansible --version
```
* **Expected Output:** Displays `ansible [core ...]`, Python version (3.10+), and executable path.

---

### Step 4: Generate SSH Key Pair on Control Node

Generate a secure Ed25519 SSH key pair (leave the passphrase empty for automated non-interactive tasks):

```bash
ssh-keygen -t ed25519 -C "ansible-control-node"
```
* Press `Enter` to accept default path (`~/.ssh/id_ed25519`).
* Press `Enter` twice for no passphrase.

---

### Step 5: Copy SSH Public Key to Target Managed VM(s)

#### Option A: VirtualBox NAT with Port Forwarding (Port 2222)
```bash
ssh-copy-id -i ~/.ssh/id_ed25519.pub -p 2222 danis@127.0.0.1
```

#### Option B: Direct VM IP (Bridged Adapter / Subnet IP)
```bash
ssh-copy-id -i ~/.ssh/id_ed25519.pub danis@192.168.1.15
```

**Verify Passwordless Login:**
```bash
ssh -p 2222 danis@127.0.0.1
# Should log in immediately without asking for a password!
exit
```

---

### Step 6: Create the Inventory File (`hosts.ini`)

In your repository directory (`c:\Users\danis\Desktop\ansible` or `/mnt/c/Users/danis/Desktop/ansible`):

```ini
# hosts.ini - Ansible Inventory

[webservers]
node1 ansible_host=127.0.0.1 ansible_port=2222 ansible_user=danis

# [dbservers]
# node2 ansible_host=192.168.1.135 ansible_port=22 ansible_user=danis

[all_servers:children]
webservers

[all:vars]
ansible_python_interpreter=/usr/bin/python3
```

---

### Step 7: Create Ansible Configuration (`ansible.cfg`)

```ini
# ansible.cfg
[defaults]
inventory = hosts.ini
remote_user = danis
host_key_checking = False
retry_files_enabled = False

[privilege_escalation]
become = True
become_method = sudo
become_user = root
become_ask_pass = False
```

* **Why `host_key_checking = False`?** Prevents interactive "Are you sure you want to continue connecting (yes/no)?" prompts from halting Ansible automation in lab environments.

---

### Step 8: Test Connectivity with the `ping` Module

Run the ad-hoc connectivity check:

```bash
ansible all -i hosts.ini -m ping
```

**Expected Output:**
```json
node1 | SUCCESS => {
    "ansible_facts": {
        "discovered_interpreter_python": "/usr/bin/python3"
    },
    "changed": false,
    "ping": "pong"
}
```

> **Note:** The Ansible `ping` module is **not** an ICMP ping. It establishes an SSH session, uploads a small Python script, executes it on the managed node, and returns JSON `{"ping": "pong"}`.

---

### Step 9: Run Ad-Hoc Diagnostic Commands

Test remote command execution across all target nodes:

```bash
# Check remote VM uptime
ansible all -i hosts.ini -m command -a "uptime"

# Check remote VM available memory
ansible all -i hosts.ini -m command -a "free -h"
```

---

## 🔍 Common Errors & Fixes

| Issue | Cause | Fix |
| :--- | :--- | :--- |
| `UNREACHABLE! Connection refused` | SSH daemon not running on VM or wrong port | Check `sudo systemctl status ssh` on VM and verify port in `hosts.ini` (e.g., `ansible_port=2222`). |
| `Permission denied (publickey)` | Public key not added to `authorized_keys` | Run `ssh-copy-id` again or verify `chmod 600 ~/.ssh/authorized_keys` on target. |
| `/usr/bin/python3: not found` | Target VM is missing Python | On target VM: `sudo apt update && sudo apt install -y python3`. |
| `Host key verification failed` | VM host keys changed after recreation | Add `host_key_checking = False` in `ansible.cfg`. |

---

## 💡 Key Learnings

1. **Zero-Agent Footprint:** Ansible simplifies infrastructure automation by leveraging existing OpenSSH and Python stacks.
2. **Control vs Managed Roles:** Only the Control Node requires Ansible installed; managed nodes remain lightweight.
3. **Inventory Decoupling:** Hosts, ports, and connection credentials are encapsulated within `hosts.ini`.
4. **Idempotent Ad-Hoc Modules:** Modules like `ping` and `command` provide immediate execution feedback before writing complex YAML playbooks.
