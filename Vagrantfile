# -*- mode: ruby -*-
# vi: set ft=ruby :

# Config Github Settings
github_username = "fideloper"
github_repo     = "Vaprobash12"
github_branch   = "master"

# Server Configuration

# Set a local private network IP address.
# See http://en.wikipedia.org/wiki/Private_network for explanation
# You can use the following IP ranges:
#   10.0.0.1    - 10.255.255.254
#   172.16.0.1  - 172.31.255.254
#   192.168.0.1 - 192.168.255.254
server_ip             = "192.168.100.11"
server_memory         = "384" # MB
server_timezone       = "UTC"
server_hostname       = "api.irail.dev"

# Database Configuration
mysql_root_password   = "root"   # We'll assume user "root"
mysql_version         = "5.5"    # Options: 5.5 | 5.6
mysql_enable_remote   = "false"  # remote access enabled when true

# Languages and Packages

# HHVM pptions
hhvm_use_fastcgi      = "false"  # Use HHVM as FastCGI (over php-fpm)
hhvm_over_php         = "false"  # Symlink HHVM to PHP, so calls to PHP run via HHVM

# PHP Options
php_version           = "latest" # Options: latest|previous|distributed   For 12.04. latest=5.5, previous=5.4, distributed=5.3
composer_packages     = [        # List any global Composer packages that you want to install
  "phpunit/phpunit:4.0.*",
  "codeception/codeception=*",
  "phpspec/phpspec:2.0.*@dev",
]
public_folder         = "/vagrant/api" # If installing Symfony or Laravel, leave this blank to default to the framework public directory


Vagrant.configure("2") do |config|

  # Set server to Ubuntu 12.04
  config.vm.box = "precise64"

  config.vm.box_url = "http://files.vagrantup.com/precise64.box"
  # If using VMWare Fusion Provider:
  # config.vm.box_url = "http://files.vagrantup.com/precise64_vmware.box"

  # Create a hostname, don't forget to put it to the `hosts` file
  # This will point to the server's default virtual host
  # TO DO: Make this work with virtualhost along-side xip.io URL
  config.vm.hostname = server_hostname

  # Create a static IP
  config.vm.network :private_network, ip: server_ip

  # Use NFS for the shared folder
  config.vm.synced_folder ".", "/vagrant",
            id: "core",
            :nfs => true,
            :mount_options => ['nolock,vers=3,udp,noatime']

  # If using VirtualBox
  config.vm.provider :virtualbox do |vb|

    # Set server memory
    vb.customize ["modifyvm", :id, "--memory", server_memory]

    # Set the timesync threshold to 10 seconds, instead of the default 20 minutes.
    # If the clock gets more than 15 minutes out of sync (due to your laptop going
    # to sleep for instance, then some 3rd party services will reject requests.
    vb.customize ["guestproperty", "set", :id, "/VirtualBox/GuestAdd/VBoxService/--timesync-set-threshold", 10000]

    # Prevent VMs running on Ubuntu to lose internet connection
    # vb.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
    # vb.customize ["modifyvm", :id, "--natdnsproxy1", "on"]

  end

  # If using VMWare Fusion
  config.vm.provider :vmware_fusion do |vb|

    # Set server memory
    vb.vmx["memsize"] = server_memory

  end

  # If using Vagrant-Cachier
  # http://fgrehm.viewdocs.io/vagrant-cachier
  if Vagrant.has_plugin?("vagrant-cachier")
    # Configure cached packages to be shared between instances of the same base box.
    # Usage docs: http://fgrehm.viewdocs.io/vagrant-cachier/usage
    config.cache.scope = :box

    config.cache.synced_folder_opts = {
        type: :nfs,
        mount_options: ['rw', 'vers=3', 'tcp', 'nolock']
    }
  end

  ####
  # Base Items
  ##########

  # Provision Base Packages
  config.vm.provision "shell", path: "https://raw.github.com/#{github_username}/#{github_repo}/#{github_branch}/scripts/base.sh"

  # Provision PHP
  config.vm.provision "shell", path: "https://raw.github.com/#{github_username}/#{github_repo}/#{github_branch}/scripts/php.sh", args: [php_version, server_timezone]


  ####
  # Web Servers
  ##########

  # Provision Apache Base
  config.vm.provision "shell", path: "https://raw.github.com/#{github_username}/#{github_repo}/#{github_branch}/scripts/apache.sh", args: [server_ip, public_folder]

  # Create Project VirtualHost
  config.vm.provision "shell", inline: "sudo vhost -s #{server_hostname} -d #{public_folder}; sudo a2ensite #{server_hostname}; sudo service apache2 restart"

  # Disable the default VirtualHost
  config.vm.provision "shell", inline: "sudo rm /etc/apache2/sites-enabled/000-default.conf; sudo service apache2 restart"

  ####
  # Frameworks and Tooling
  ##########

  # Provision Composer
  config.vm.provision "shell", path: "https://raw.github.com/#{github_username}/#{github_repo}/#{github_branch}/scripts/composer.sh", privileged: false, args: composer_packages.join(" ")

end
