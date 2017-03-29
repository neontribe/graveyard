Vagrant.require_version ">= 1.9"

Vagrant.configure("2") do |config|

    config.vm.provider :virtualbox do |v|
        v.name = "default"
        v.customize [
            "modifyvm", :id,
            "--name", "default",
            "--memory", 512,
            "--natdnshostresolver1", "on",
            "--cpus", 1,
        ]
    end

    config.vm.box = "ubuntu/xenial64"


    config.vm.network :private_network, ip: "192.168.33.99"
    config.ssh.forward_agent = true

    config.vm.provision "ansible" do |ansible|
        ansible.playbook = "ansible/playbook.yml"
        ansible.inventory_path = "ansible/inventories/dev"
        ansible.limit = 'all'
    end

    config.vm.synced_folder "./", "/vagrant", type: "nfs"
end

# vim: ai ts=2 sts=2 et sw=2 ft=ruby
