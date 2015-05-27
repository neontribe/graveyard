# -*- mode: ruby -*-
# vi: set ft=ruby ts=2 sw=2 tw=0 et :

role = File.basename(File.expand_path(File.dirname(__FILE__)))
brand = role.split("-").pop()

boxes = [
  {
    :name => "ubuntu-1404",
    :box => "opscode-ubuntu-14.04",
    :url => "http://opscode-vm-bento.s3.amazonaws.com/vagrant/virtualbox/opscode_ubuntu-14.04_chef-provisionerless.box",
    :ip => '10.0.0.12',
    :cpu => "50",
    :cpus => 4,
    :ram => "4096"
  },
]

Vagrant.configure("2") do |config|
  boxes.each do |box|
    config.vm.define box[:name] do |vms|
      vms.vm.box = box[:box]
      vms.vm.box_url = box[:url]
      vms.vm.hostname = role

      vms.vm.provider "virtualbox" do |v|
        v.customize ["modifyvm", :id, "--cpuexecutioncap", box[:cpu]]
        v.customize ["modifyvm", :id, "--memory", box[:ram]]
        v.customize ["modifyvm", :id, "--cpus", box[:cpus]]
      end

      vms.vm.network :private_network, ip: box[:ip]

      vms.vm.provision :ansible do |ansible|
        ansible.playbook = "playbook.yml"
        ansible.verbose = "vv"
        ansible.limit = 'all'
        ansible.extra_vars = {
            private_interface: box[:ip],
            hostname: "#{role}",
            server_type: "desktop",
            cottage_alias_name: "neon#{brand}",
            drupal: {
                dbname: "live_#{brand}",
                dbuser: "live_#{brand}",
            },
            cottage_brand_code: "#{brand}",
            admin_email: "info@neontribe.co.uk",
            servername: "testserver",
            cottage_target: "/vagrant/drupal",
            doc_root: "/vagrant/drupal",
            ssl: {
                key: "ssl-cert-snakeoil.key",
                crt: "ssl-cert-snakeoil.pem",
                chain: "chain.crt",
            },
            neon_user: "vagrant",
            remote_host: "192.168.21.44",
            remote_user: "ansible",
        }
      end
    end
  end
end
