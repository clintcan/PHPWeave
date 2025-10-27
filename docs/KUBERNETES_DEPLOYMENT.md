# PHPWeave Kubernetes Deployment Guide

This guide covers deploying PHPWeave on Kubernetes with production-ready configurations including auto-scaling, persistent storage, and MySQL database.

## Table of Contents
- [Prerequisites](#prerequisites)
- [Quick Start](#quick-start)
- [Architecture Overview](#architecture-overview)
- [Deployment Steps](#deployment-steps)
- [Configuration](#configuration)
- [Scaling](#scaling)
- [Monitoring](#monitoring)
- [Troubleshooting](#troubleshooting)
- [Production Checklist](#production-checklist)

## Prerequisites

- Kubernetes cluster (1.19+)
- kubectl CLI configured
- Docker registry access (or use public ghcr.io)
- NGINX Ingress Controller installed
- cert-manager installed (for SSL/TLS)
- Metrics Server installed (for HPA)

## Quick Start

```bash
# Create namespace
kubectl create namespace phpweave

# Deploy using kustomize
kubectl apply -k k8s/

# Or deploy individual manifests
kubectl apply -f k8s/ -n phpweave

# Check deployment status
kubectl get all -n phpweave

# Get ingress URL
kubectl get ingress -n phpweave
```

## Architecture Overview

The Kubernetes deployment includes:

- **PHPWeave Application** (3 replicas by default)
  - Deployment with rolling updates
  - HorizontalPodAutoscaler (3-10 pods)
  - APCu caching enabled (container-isolated)
  - Health checks (liveness/readiness probes)

- **MySQL Database** (StatefulSet)
  - Single instance with persistent storage
  - 10Gi PVC for data persistence
  - Health monitoring

- **Storage**
  - PersistentVolumeClaim for shared storage
  - EmptyDir for pod-local cache

- **Networking**
  - ClusterIP Service with session affinity
  - NGINX Ingress with SSL/TLS
  - Automatic certificate management

## Deployment Steps

### 1. Build and Push Docker Image

```bash
# Build the image
docker build -t ghcr.io/yourusername/phpweave:latest .

# Push to registry
docker push ghcr.io/yourusername/phpweave:latest
```

### 2. Configure Secrets

Create a `secrets.env` file in the `k8s/` directory:

```env
DBUSER=phpweave_user
DBPASSWORD=your_secure_password_here
ERROR_EMAIL=admin@example.com
```

Or create secrets manually:

```bash
kubectl create secret generic phpweave-secret \
  --from-literal=DBUSER=phpweave_user \
  --from-literal=DBPASSWORD=secure_password \
  --from-literal=ERROR_EMAIL=admin@example.com \
  -n phpweave

kubectl create secret generic mysql-secret \
  --from-literal=root-password=mysql_root_password \
  -n phpweave
```

### 3. Update Configuration

Edit `k8s/configmap.yaml`:
- Set appropriate environment values
- Configure timezone and error reporting

Edit `k8s/ingress.yaml`:
- Replace `phpweave.example.com` with your domain
- Configure SSL certificate issuer

Edit `k8s/deployment.yaml`:
- Update image repository if using private registry
- Adjust resource limits based on needs

### 4. Deploy to Kubernetes

```bash
# Create namespace
kubectl create namespace phpweave

# Deploy MySQL first
kubectl apply -f k8s/mysql.yaml -n phpweave

# Wait for MySQL to be ready
kubectl wait --for=condition=ready pod -l app=mysql -n phpweave --timeout=120s

# Deploy application
kubectl apply -f k8s/pvc.yaml -n phpweave
kubectl apply -f k8s/configmap.yaml -n phpweave
kubectl apply -f k8s/secret.yaml -n phpweave
kubectl apply -f k8s/deployment.yaml -n phpweave
kubectl apply -f k8s/service.yaml -n phpweave
kubectl apply -f k8s/hpa.yaml -n phpweave
kubectl apply -f k8s/ingress.yaml -n phpweave
```

### 5. Verify Deployment

```bash
# Check pods
kubectl get pods -n phpweave

# Check services
kubectl get svc -n phpweave

# Check ingress
kubectl get ingress -n phpweave

# View logs
kubectl logs -l app=phpweave -n phpweave

# Check HPA status
kubectl get hpa -n phpweave
```

## Configuration

### Environment Variables

Configured via ConfigMap (`k8s/configmap.yaml`):
- `DEBUG`: Set to "0" for production
- `APP_ENV`: Environment name
- `DBHOST`: MySQL service name
- `CACHE_ENABLED`: Enable caching
- `APCU_ENABLED`: Enable APCu cache

### Resource Limits

Default limits in `k8s/deployment.yaml`:
```yaml
resources:
  requests:
    memory: "128Mi"
    cpu: "100m"
  limits:
    memory: "256Mi"
    cpu: "500m"
```

Adjust based on application needs and available resources.

### Storage Classes

The deployment uses `standard` storage class by default. Update in:
- `k8s/pvc.yaml` - Application storage
- `k8s/mysql.yaml` - Database storage

For cloud providers:
- AWS: `gp2` or `gp3`
- GCP: `pd-standard` or `pd-ssd`
- Azure: `managed-premium`

## Scaling

### Horizontal Pod Autoscaling

The HPA automatically scales pods based on CPU/memory:
- Min replicas: 3
- Max replicas: 10
- CPU target: 70%
- Memory target: 80%

```bash
# Check HPA status
kubectl get hpa phpweave-hpa -n phpweave

# Manually scale
kubectl scale deployment phpweave --replicas=5 -n phpweave
```

### Vertical Scaling

Update resource limits in `k8s/deployment.yaml`:

```yaml
resources:
  requests:
    memory: "256Mi"
    cpu: "250m"
  limits:
    memory: "512Mi"
    cpu: "1000m"
```

Apply changes:
```bash
kubectl apply -f k8s/deployment.yaml -n phpweave
```

## Monitoring

### Application Health

```bash
# Check pod health
kubectl describe pod -l app=phpweave -n phpweave

# View application logs
kubectl logs -f -l app=phpweave -n phpweave

# Check readiness
kubectl get pods -n phpweave -o wide
```

### Database Health

```bash
# Check MySQL status
kubectl exec -it mysql-0 -n phpweave -- mysql -u root -p -e "SHOW STATUS;"

# View MySQL logs
kubectl logs mysql-0 -n phpweave
```

### Metrics

```bash
# Pod metrics
kubectl top pods -n phpweave

# Node metrics
kubectl top nodes

# HPA metrics
kubectl describe hpa phpweave-hpa -n phpweave
```

## Troubleshooting

### Common Issues

#### Pods Not Starting

```bash
# Check pod status
kubectl describe pod <pod-name> -n phpweave

# Check events
kubectl get events -n phpweave --sort-by='.lastTimestamp'
```

#### Database Connection Issues

```bash
# Test MySQL connection
kubectl exec -it <phpweave-pod> -n phpweave -- mysql -h mysql-service -u phpweave_user -p

# Check MySQL service
kubectl get svc mysql-service -n phpweave
```

#### Storage Issues

```bash
# Check PVC status
kubectl get pvc -n phpweave

# Describe PVC
kubectl describe pvc phpweave-storage -n phpweave
```

#### Ingress Not Working

```bash
# Check ingress controller
kubectl get pods -n ingress-nginx

# Check ingress status
kubectl describe ingress phpweave-ingress -n phpweave

# Test service directly
kubectl port-forward svc/phpweave 8080:80 -n phpweave
```

### Debug Commands

```bash
# Execute commands in pod
kubectl exec -it <pod-name> -n phpweave -- /bin/bash

# Copy files from pod
kubectl cp phpweave/<pod-name>:/var/www/html/coreapp/error.log ./error.log

# View pod YAML
kubectl get pod <pod-name> -n phpweave -o yaml

# Edit deployment
kubectl edit deployment phpweave -n phpweave
```

## Production Checklist

### Security

- [ ] Update all default passwords in secrets
- [ ] Enable NetworkPolicies for pod isolation
- [ ] Configure RBAC for service accounts
- [ ] Use private container registry
- [ ] Enable pod security policies
- [ ] Scan images for vulnerabilities

### Performance

- [ ] Configure appropriate resource limits
- [ ] Enable APCu caching (already in Dockerfile)
- [ ] Configure HPA thresholds based on load testing
- [ ] Use SSD storage classes for database
- [ ] Enable connection pooling for database

### Reliability

- [ ] Configure pod disruption budgets
- [ ] Set up regular database backups
- [ ] Implement health checks
- [ ] Configure rolling update strategy
- [ ] Set up monitoring and alerting

### Monitoring

- [ ] Deploy Prometheus/Grafana stack
- [ ] Configure application metrics
- [ ] Set up log aggregation (ELK/EFK)
- [ ] Create alerting rules
- [ ] Monitor ingress metrics

### Backup

- [ ] Implement MySQL backup CronJob
- [ ] Backup persistent volumes
- [ ] Document restore procedures
- [ ] Test disaster recovery

## Advanced Configuration

### Multi-Environment Setup

Create overlays for different environments:

```bash
k8s/
├── base/
│   └── kustomization.yaml
├── overlays/
│   ├── dev/
│   │   └── kustomization.yaml
│   ├── staging/
│   │   └── kustomization.yaml
│   └── production/
│       └── kustomization.yaml
```

Deploy specific environment:
```bash
kubectl apply -k k8s/overlays/production/
```

### Blue-Green Deployment

```yaml
# Create green deployment
kubectl set image deployment/phpweave phpweave=ghcr.io/yourusername/phpweave:v2 -n phpweave

# Switch service to green
kubectl patch service phpweave -p '{"spec":{"selector":{"version":"green"}}}' -n phpweave

# Delete blue deployment after verification
kubectl delete deployment phpweave-blue -n phpweave
```

### Database Migrations

```bash
# Run migrations in init container
kubectl exec -it <pod-name> -n phpweave -- php migrate.php

# Or use Kubernetes Job
kubectl apply -f k8s/migration-job.yaml
```

### Secrets Management

Consider using:
- Kubernetes Secrets with encryption at rest
- HashiCorp Vault
- AWS Secrets Manager
- Azure Key Vault
- Google Secret Manager

## Cleanup

Remove all resources:

```bash
# Delete namespace (removes everything)
kubectl delete namespace phpweave

# Or delete individual resources
kubectl delete -f k8s/ -n phpweave
```

## Additional Resources

- [Kubernetes Documentation](https://kubernetes.io/docs/)
- [NGINX Ingress Controller](https://kubernetes.github.io/ingress-nginx/)
- [cert-manager](https://cert-manager.io/)
- [Horizontal Pod Autoscaler](https://kubernetes.io/docs/tasks/run-application/horizontal-pod-autoscale/)
- [Persistent Volumes](https://kubernetes.io/docs/concepts/storage/persistent-volumes/)

## Support

For PHPWeave-specific issues, see:
- Main documentation: `docs/README.md`
- Docker guide: `docs/DOCKER_DEPLOYMENT.md`
- Performance tuning: `docs/OPTIMIZATIONS_APPLIED.md`