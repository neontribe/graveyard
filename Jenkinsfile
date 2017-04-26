pipeline {
    agent any

    stages {
        stage('Build') {
            steps {
                git url: 'git@github.com:neontabs/nt8-installer.git', env.BRANCH_NAME
                cd web && drush -y site-install --db-url="sqlite://sites/default/files/.ht.sqlite" --account-mail="${USER}@neontribe.co.uk" --account-name=superadmin --site-mail="${USER}@neontribe.co.uk" --site-name=EntyAte
            }
        }
        stage('Test'){
            steps {
                echo "Run functional tests"
            }
        }
    }
}

