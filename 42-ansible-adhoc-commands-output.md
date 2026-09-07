# Lab 42: Ansible Ad-Hoc Commands & Output Analysis

Welcome to **Lab 42** of the DevOps & Cloud Automation series! In this lab, we master **Ansible Ad-Hoc Commands** — executing targeted tasks across multiple virtual machines (`vm1` and `vm2`), working with core modules, demonstrating idempotency, and analyzing execution outputs and return codes.

---

## 🎯 Lab Objectives

1. Understand what **Ad-Hoc commands** are and when to use them over full playbooks.
2. Master the CLI anatomy of the `ansible` command (`-i`, `-m`, `-a`, `-b`, `-u`, `-v`).
3. Execute commands across both multi-host groups (`studentvms`) and targeted individual nodes (`vm1`, `vm2`).
4. Execute and examine outputs from core built-in modules:
   - `ping` (connectivity & Python runtime validation)
   - `command` (direct execution without shell overhead)
   - `shell` (pipelines `|`, redirections, and grep filters)
   - `copy` (remote file deployment with inline content)
   - `file` (directory and permission management)
   - `setup` (gathering system facts and hardware/OS metadata)
   - `apt` (package installation with sudo privilege escalation)
5. Demystify Ansible **idempotency** and **color codes** (Green, Yellow/Cyan, Red) along with JSON return keys (`changed`, `rc`, `stdout`, `stderr`).

---

## 🏗️ Ad-Hoc Commands vs. Playbooks

```text
+-------------------------+-----------------------------------+----------------------------------+
| Feature                 | Ad-Hoc Commands                   | Playbooks (.yml)                 |
+-------------------------+-----------------------------------+----------------------------------+
| Purpose                 | Quick one-off actions / checks    | Complex multi-step orchestrations|
| Reusability             | Low (run interactively from CLI)  | High (stored in Git repos)       |
| Complexity              | Single module per command         | Multi-play, multi-task, roles    |
| Example Use Cases       | Reboot servers, ping, check RAM   | Deploy full LAMP/MERN web stack  |
+-------------------------+-----------------------------------+----------------------------------+
```

---

## 🔍 The Anatomy of an Ad-Hoc Command

Every Ansible ad-hoc command follows this structure:

```bash
ansible <host-pattern> -i <inventory> -m <module_name> -a "<arguments>" [flags]
```

### Options Breakdown:
* `<host-pattern>`: Targets from inventory (`all`, `studentvms`, `vm1`, `vm2`, `localhost`).
* `-i hosts.ini`: Specifies the inventory phonebook.
* `-m <module>`: The Ansible module to execute (defaults to `command`).
* `-a "<args>"`: Arguments passed to the module.
* `-b` / `--become`: Elevates privileges to `sudo/root`.
* `-v` / `-vvv`: Increases verbosity for troubleshooting.

---

## 📋 Lab Setup: Multi-VM Inventory & Configuration

### 1. Inventory (`hosts.ini`)
```ini
# ==============================================================================
# ANSIBLE INVENTORY FILE (hosts.ini)
# ==============================================================================

# Local Control Node
[control]
localhost ansible_connection=local

# Managed VirtualBox Ubuntu VMs
[studentvms]
vm1 ansible_host=192.168.56.101
vm2 ansible_host=192.168.56.102

[studentvms:vars]
ansible_user=danish

[managed_nodes:children]
studentvms

# Parent Group
[all_servers:children]
control
managed_nodes

[all:vars]
ansible_python_interpreter=/usr/bin/python3
```

### 2. Configuration (`ansible.cfg`)
```ini
[defaults]
inventory = hosts.ini
host_key_checking = False
remote_user = danish
retry_files_enabled = False

[privilege_escalation]
become = False
become_method = sudo
become_user = root
become_ask_pass = False
```

---

## 🛠️ Hands-On Execution & Output Walkthrough

---

### Task 1: Multi-VM Connectivity Check (`ping` module)

Connect to all student VMs simultaneously, verify SSH key authentication, and validate the remote Python interpreter:

```bash
ansible studentvms -i hosts.ini -m ping
```

**Real Output:**
```json
192.168.56.101 | SUCCESS => {
    "changed": false,
    "ping": "pong"
}
192.168.56.102 | SUCCESS => {
    "changed": false,
    "ping": "pong"
}
```

* **Color:** 🟢 **GREEN (SUCCESS)**
* **`"changed": false`:** Read-only inspection; no files or configs were altered.
* **`"ping": "pong"`:** Python test script executed successfully on both remote targets.

---

### Task 2: System Diagnostics (`command` module)

The `command` module is the default module. It runs binaries directly on targets without shell overhead.

#### 1. Check System Uptime Across All VMs:
```bash
ansible studentvms -i hosts.ini -m command -a "uptime"
```

**Real Output:**
```text
192.168.56.101 | CHANGED | rc=0 >>
 15:44:43 up  1:01,  1 user,  load average: 0.22, 0.20, 0.14

192.168.56.102 | CHANGED | rc=0 >>
 16:39:58 up 22 min,  1 user,  load average: 0.09, 0.10, 0.17
```

#### 2. Check Memory (RAM) Usage:
```bash
ansible studentvms -i hosts.ini -m command -a "free -h"
```

**Real Output:**
```text
192.168.56.101 | CHANGED | rc=0 >>
               total        used        free      shared  buff/cache   available
Mem:           2.9Gi       320Mi       2.1Gi       1.0Mi       512Mi       2.4Gi
Swap:          2.0Gi          0B       2.0Gi

192.168.56.102 | CHANGED | rc=0 >>
               total        used        free      shared  buff/cache   available
Mem:           2.9Gi       315Mi       2.1Gi       1.0Mi       508Mi       2.4Gi
Swap:          2.0Gi          0B       2.0Gi
```

#### 3. Check Disk Space on Root (`/`):
```bash
ansible studentvms -i hosts.ini -m command -a "df -h /"
```

---

### Task 3: Shell & Pipeline Operations (`shell` module)

Unlike `command`, the `shell` module invokes `/bin/sh` on the remote machines, enabling pipe operators (`|`), redirections (`>`), and regex filters.

#### Filter IP Addresses Using Pipelines:
```bash
ansible studentvms -i hosts.ini -m shell -a "ip a | grep -i 192.168"
```

**Real Output:**
```text
192.168.56.101 | CHANGED | rc=0 >>
    inet 192.168.56.101/24 metric 1024 brd 192.168.56.255 scope global dynamic enp0s8

192.168.56.102 | CHANGED | rc=0 >>
    inet 192.168.56.102/24 metric 1024 brd 192.168.56.255 scope global dynamic enp0s8
```

#### Check OS Release via Pipeline:
```bash
ansible studentvms -i hosts.ini -m shell -a "cat /etc/os-release | grep PRETTY_NAME"
```

**Real Output:**
```text
192.168.56.101 | CHANGED | rc=0 >>
PRETTY_NAME="Ubuntu 26.04 LTS"

192.168.56.102 | CHANGED | rc=0 >>
PRETTY_NAME="Ubuntu 26.04 LTS"
```

---

### Task 4: Targeted Deployment & Idempotency (`copy` module)

Ansible allows targeting individual hosts instead of entire groups.

#### 1. Deploy File ONLY to `vm2`:
```bash
ansible vm2 -i hosts.ini -m copy -a "content='Hello! This file lives ONLY on VM 2\n' dest=/tmp/vm2_file.txt"
```

**Real Output:**
```json
vm2 | CHANGED => {
    "changed": true,
    "checksum": "37edcba454316f669f4152741157aa4d0f67c04d",
    "dest": "/tmp/vm2_file.txt",
    "gid": 1000,
    "group": "danish",
    "md5sum": "d9962b33cbbce3fef4fe5dccd8847958",
    "mode": "0664",
    "owner": "danish",
    "size": 36,
    "state": "file",
    "uid": 1000
}
```

#### 2. Verify Content on `vm2`:
```bash
ansible vm2 -i hosts.ini -m command -a "cat /tmp/vm2_file.txt"
```

**Real Output:**
```text
vm2 | CHANGED | rc=0 >>
Hello! This file lives ONLY on VM 2
```

#### 3. Prove Host Isolation (Verifying `vm1` was not modified):
```bash
ansible vm1 -i hosts.ini -m command -a "cat /tmp/vm2_file.txt"
```

**Real Output:**
```text
vm1 | FAILED | rc=1 >>
cat: /tmp/vm2_file.txt: No such file or directory
```
*Proves complete isolation between target managed nodes.*

#### 4. Deploy File to `vm1`:
```bash
ansible vm1 -i hosts.ini -m copy -a "content='Hello! This file lives ONLY on VM 1\n' dest=/tmp/vm1_file.txt"
ansible vm1 -i hosts.ini -m command -a "cat /tmp/vm1_file.txt"
```

#### 5. Witnessing Idempotency Live:
When running the exact same `copy` command on `vm2` a second time:
```json
vm2 | SUCCESS => {
    "changed": false,
    "dest": "/tmp/vm2_file.txt",
    "gid": 1000,
    "group": "danish",
    "mode": "0664",
    "owner": "danish",
    "size": 36,
    "state": "file"
}
```
* **Why `"changed": false`?** Ansible checked the file checksum (`md5sum`), verified the content already matched the desired state, and safely skipped redundant disk writes!

---

### Task 5: Gathering Hardware & System Facts (`setup` module)

Ansible automatically collects system facts before playbook runs. You can inspect specific metadata:

```bash
ansible studentvms -i hosts.ini -m setup -a "filter=ansible_distribution*"
```

**Real Output:**
```json
192.168.56.101 | SUCCESS => {
    "ansible_facts": {
        "ansible_distribution": "Ubuntu",
        "ansible_distribution_major_version": "26",
        "ansible_distribution_version": "26.04"
    },
    "changed": false
}
192.168.56.102 | SUCCESS => {
    "ansible_facts": {
        "ansible_distribution": "Ubuntu",
        "ansible_distribution_major_version": "26",
        "ansible_distribution_version": "26.04"
    },
    "changed": false
}
```

---

### Task 6: Package Management with Sudo (`apt` module with `-b`)

Execute administrative package installation using privilege escalation:

```bash
# Update cache and install tree utility across all student VMs
ansible studentvms -i hosts.ini -b -m apt -a "name=tree state=present"
```

Verify the installation:
```bash
ansible studentvms -i hosts.ini -m command -a "tree --version"
```

**Real Output:**
```text
192.168.56.101 | CHANGED | rc=0 >>
tree v2.2.1 © 1996 - 2024 by Steve Baker

192.168.56.102 | CHANGED | rc=0 >>
tree v2.2.1 © 1996 - 2024 by Steve Baker
```

---

## 🎨 Ansible Color Codes & Return Values Decoded

| Indicator | Status | Meaning |
| :--- | :---: | :--- |
| 🟢 **Green** | `SUCCESS / OK` | Command succeeded. System was already in desired state (`changed: false`). |
| 🟡 **Yellow / Cyan** | `CHANGED` | Command succeeded and altered system state (`changed: true`). |
| 🔴 **Red** | `FAILED / UNREACHABLE` | Command failed (`rc != 0`) or node was unreachable over SSH. |

### Key JSON Return Attributes:
* **`rc`:** Linux return code (`0` = success, non-zero = error).
* **`changed`:** Boolean indicating whether any state change occurred on the managed node.
* **`stdout` / `stdout_lines`:** Standard output text returned by the executed command.
* **`stderr` / `stderr_lines`:** Standard error output if something failed.

---

## 💡 Key Learnings

1. **Fleet Automation:** A single ad-hoc command can query and configure dozens or hundreds of servers simultaneously.
2. **Selective Targeting:** Inventory aliases (`vm1`, `vm2`) allow surgical execution on individual nodes without modifying the wider cluster.
3. **Idempotent Safety:** Modules like `copy`, `file`, and `apt` ensure tasks only make changes when the current state does not match the desired state.
4. **Command vs. Shell:** Use `command` for predictable binary execution; use `shell` when pipelines (`|`) or shell operators are needed.

---

## 🔄 Standard Git & Tracker Sync Workflow

To commit, push, and update your **Google Sheets Lab Tracker**:

```powershell
# 1. From repository root (C:\Users\danis\Desktop\ansible)
cd C:\Users\danis\Desktop\ansible
git status
git add hosts.ini 42-ansible-adhoc-commands-output.md
git commit -m "feat/docs: add 42 ansible adhoc commands and output guide"
git push origin main

# 2. Sync Google Sheet Tracker
cd C:\Users\danis\Desktop\python-automation
python python-automation/gsheets-automation/35-copy-lab-tracker-automation.py
```

Target row **Lab 42** will automatically turn **"Yes" and Green!** 🟢
