Vagrant.require_version ">= 1.9"
Vagrant::DEFAULT_SERVER_URL.replace('https://vagrantcloud.com')

  begin
    brand = ENV['BRAND']
    if !brand
      print "No brand code passed\n"
      print "Use BRAND=zz vagrant up\n\n"
      exit
    end
  end

Vagrant.configure("2") do |config|

  config.vm.provider :virtualbox do |v|
    v.name = "default"
    v.customize [
      "modifyvm", :id,
      "--name", "default",
      "--memory", 1024,
      "--natdnshostresolver1", "on",
      "--cpus", 1,
    ]
  end

  config.vm.box = "ubuntu/xenial64"


  config.vm.network :private_network, ip: "192.168.33.99"
  config.ssh.forward_agent = true

  config.vm.provision "ansible" do |ansible|
    ansible.playbook = "ansible/vagrant.yml"
    ansible.inventory_path = "ansible/inventories/dev"
    ansible.limit = 'all'
    ansible.extra_vars = { installer_root: '/vagrant/web', brand: ENV['BRAND'] }
    ansible.verbose = "vvv"
  end

  config.vm.synced_folder "./", "/vagrant/web", type: "nfs", mount_options: ['rw', 'vers=3', 'tcp', 'fsc' ,'actimeo=2', 'nolock']
end

# vim: ai ts=2 sts=2 et sw=2 ft=ruby
