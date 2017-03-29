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
    config.vm.box_url = "https://cloud-images.ubuntu.com/xenial/current/xenial-server-cloudimg-amd64-vagrant.box"


    config.vm.network :private_network, ip: "192.168.33.99"
    config.ssh.forward_agent = true

    # If ansible is in your path it will provision from your HOST machine
    # If ansible is not found in the path it will be instaled in the VM and provisioned from there
    config.vm.provision "ansible" do |ansible|
        ansible.playbook = "ansible/playbook.yml"
        ansible.inventory_path = "ansible/inventories/dev"
        ansible.limit = 'all'
    end

    config.vm.synced_folder "./", "/vagrant", type: "nfs"
end
