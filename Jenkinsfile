pipeline {
	agent any
	stages {
		stage('Checkout Code') {
			steps {
				git(url: 'https://git.torinsk.ru/melnichenko/tori_debian.git', branch: 'main')
			}
		}
	}
}
