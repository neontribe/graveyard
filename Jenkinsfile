pipeline {
    agent any

    stages {
        stage('Build') {
            steps {
                sh 'echo `pwd`'
            }
        }
        stage('Site install') {
            steps {
                sh 'cd web && drush -y site-install --db-url="sqlite://sites/default/files/.ht.sqlite" --account-mail="${USER}@neontribe.co.uk" --account-name=superadmin --site-mail="${USER}@neontribe.co.uk" --site-name=EntyAte'
            }
        }
    }
}

